<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Stars_Rating' ) ) :
	/**
	 * Class Stars_Rating
	 *
	 * Plugin's main class.
	 *
	 * @since 1.0.0
	 */
	final class Stars_Rating {
		/**
		 * Stars Rating Version
		 *
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * Single instance of Class.
		 *
		 * @var Stars_Rating
		 * @since 1.0.0
		 */
		protected static $_instance;

		/**
		 * Provides singleton instance.
		 *
		 * @return self instance
		 * @since 1.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Status of the stars rating for the current post type
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public static function status() {

			$enabled_posts = get_option( 'enabled_post_types', array( 'post', 'page' ) );
			$post_status   = get_post_meta( get_the_ID(), 'sr-comments-rating', true );

			if ( ! is_array( $enabled_posts ) ) {
				$enabled_posts = (array) $enabled_posts;
			}

			$status = ( in_array( get_post_type(), $enabled_posts ) && ( '0' !== $post_status ) ) ? true : false;

			return $status;
		}

		/**
		 * Stars_Rating constructor.
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->init_hooks();

			add_shortcode( 'stars_rating_avg', array( $this, 'rating_average_shortcode' ) );

			// Stars Rating plugin loaded action hook
			do_action( 'star_ratings_loaded' );

		}

		/**
		 * Initialize hooks.
		 *
		 * @since 1.0.0
		 */
		public function init_hooks() {

			add_action( 'comment_form_logged_in_before', array( $this, 'comment_form_fields' ) );
			add_action( 'comment_form_top', array( $this, 'comment_form_fields' ) );
			add_filter( 'preprocess_comment', array( $this, 'verify_comment_rating' ) );
			add_action( 'comment_post', array( $this, 'save_comment_rating' ) );
			add_filter( 'comment_text', array( $this, 'modify_comment' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_plugin_files' ) );

			$avg_rating_display  = get_option( ' avg_rating_display', 'show' );
			$google_search_stars = get_option( ' google_search_stars', 'show' );

			if ( 'show' == $avg_rating_display ) {
				add_filter( "comments_template", array( $this, 'rating_average_markup' ) );
			}
			if ( 'show' == $google_search_stars ) {
				add_action( 'wp_head', array( $this, 'add_reviews_schema' ) );
			}
		}

		public function add_reviews_schema() {

			if ( ! self::status() ) {
				return;
			}

			$schema_name  = ucfirst( get_post_type() );
			$schema_title = get_the_title();
			$rating_stat  = $this->rating_stat();
			$review_type  = get_option( 'google_search_stars_type' );

			if ( ! empty( $review_type ) ) {
				$schema_name = $review_type;
			}

			echo '<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "' . esc_attr( $schema_name ) . '",
  "name": "' . esc_attr( $schema_title ) . '",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "' . floatval( $rating_stat['avg'] ) . '",
    "bestRating": "5",
    "ratingCount": "' . absint( $rating_stat['count'] ) . '"
  }
}</script>';
		}

		/**
		 * Add fields after default fields above the comment box, always visible
		 */
		public function comment_form_fields() {

			if ( ! self::status() ) {
				return;
			}

			$require_rating = get_option( 'require_rating', 'no' );
			$stars_style    = get_option( 'stars_style', 'regular' );
			?>
			<div id="stars-rating-review">
				<div class="rating-plate stars-style-<?php echo sanitize_html_class( $stars_style ); ?>">
					<select id="rate-it" class="require-<?php echo sanitize_html_class( $require_rating ); ?>" name="rating">
						<?php
						$selected_for = 5;
						for ( $i = 1; $i <= 5; $i ++ ) {
							$selected = ( $i == $selected_for ) ? "selected" : "";
							echo '<option value="' . esc_attr( $i ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $i ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>
			<?php
		}

		/**
		 * Add the filter to check whether the comment rating has been set
		 */
		public function verify_comment_rating( $comment_data ) {

			if ( ( isset( $_POST['rating'] ) ) && ( $_POST['rating'] == '' ) ) {

				wp_die( esc_html__( 'Error: You did not add a rating. Hit the Back button on your Web browser and resubmit your comment with a rating.', 'stars-rating' ) );
			}

			return $comment_data;
		}

		/**
		 * Save the comment rating along with comment
		 */
		public function save_comment_rating( $comment_id ) {

			// check if it's a reply then do nothing
			$comment = get_comment( $comment_id, ARRAY_A );
			if ( ! empty( $comment['comment_parent'] ) ) {
				return;
			}

			if ( ( isset( $_POST['rating'] ) ) && ( $_POST['rating'] != '' ) ) {
				$rating = min( max( 1, $_POST['rating'] ), 5 );;
				add_comment_meta( $comment_id, 'rating', $rating );
			}

		}

		/**
		 * Add the comment rating (saved earlier) to the comment text
		 * You can also output the comment rating values directly to the comments template
		 */
		public function modify_comment( $comment ) {

			if ( ! self::status() ) {
				return $comment;
			}

			if ( $rating = get_comment_meta( get_comment_ID(), 'rating', true ) ) {
				$rating = '<p>' . wp_kses_post( $this->rating_stars( $rating ) ) . '</p>';

				return $comment . $rating;
			} else {
				return $comment;
			}
		}

		/**
		 * Display average rating based on approved comments with rating
		 */
		public function rating_stat() {

			if ( ! self::status() ) {
				return;
			}

			$args = array(
				'post_id' => get_the_ID(),
				'status'  => 'approve'
			);

			$comments = get_comments( $args );
			$ratings  = array();
			$count    = 0;

			foreach ( $comments as $comment ) {

				$rating = get_comment_meta( $comment->comment_ID, 'rating', true );

				if ( ! empty( $rating ) ) {
					$ratings[] = min( max( 1, $rating ), 5 );
					$count ++;
				}
			}

			if ( 0 != count( $ratings ) ) {

				$avg = ( array_sum( $ratings ) / count( $ratings ) );

				return array(
					'avg'   => round( $avg, 1 ),
					'count' => $count
				);
			}

			return false;

		}

		public function rating_average_markup() {

			if ( ! self::status() ) {
				return;
			}

			$rating_stat = $this->rating_stat();

			if ( $rating_stat ) {
				echo '<div class="stars-avg-rating">';
				echo wp_kses_post( $this->rating_stars( $rating_stat['avg'] ) );
				echo floatval( $rating_stat['avg'] ) . ' ' . esc_html__( 'based on', 'stars-rating' ) . ' ' . absint( $rating_stat['count'] ) . ' ' . esc_html__( 'reviews', 'stars-rating' );
				echo '</div>';
			}
		}

		public function rating_average_shortcode() {

			if ( ! self::status() ) {
				return;
			}

			$rating_stat = $this->rating_stat();

			if ( $rating_stat ) {
				ob_start();
				echo '<div class="stars-avg-rating">';
				echo wp_kses_post( $this->rating_stars( $rating_stat['avg'] ) );
				echo floatval( $rating_stat['avg'] ) . ' ' . esc_html__( 'based on', 'stars-rating' ) . ' ' . absint( $rating_stat['count'] ) . ' ' . esc_html__( 'reviews', 'stars-rating' );
				echo '</div>';
				$output = ob_get_clean();

				return $output;
			}
		}


		/**
		 * Display rated stars based on given number of rating
		 *
		 * @param int
		 *
		 * @return string
		 */
		public function rating_stars( $rating ) {

			$rating = absint( round( $rating ) );

			$output = '';

			if ( ! empty( $rating ) ) {

				$stars_style = sanitize_html_class( get_option( 'stars_style', 'regular' ) );
				$output      = '<span class="rating-stars">';

				for ( $count = 1; $count <= $rating; $count ++ ) {
					$output .= "<i class='fa stars-style-{$stars_style} rated'></i>";
				}

				$unrated = 5 - $rating;
				for ( $count = 1; $count <= $unrated; $count ++ ) {
					$output .= "<i class='fa stars-style-{$stars_style}'></i>";
				}

				$output .= '</span>';
			}

			return $output;
		}

		public function enqueue_plugin_files() {

			if ( ! self::status() ) {
				return;
			}

			$plugin_url = WP_PLUGIN_URL;

			$plugin_public_url = $plugin_url . '/stars-rating/public/';

			// fontawesome
			wp_enqueue_style(
				'fontawesome',
				$plugin_public_url . 'css/font-awesome.min.css',
				array(),
				'4.7.0'
			);

			// bar rating theme
			wp_enqueue_style(
				'bar-rating-theme',
				$plugin_public_url . 'css/fontawesome-stars.css',
				array(),
				'2.6.3'
			);

			// plugin css
			wp_enqueue_style(
				'stars-rating-public',
				$plugin_public_url . 'css/stars-rating-public.css',
				array(),
				'1.0.0'
			);

			// bar rating
			wp_enqueue_script(
				'bar-rating',
				$plugin_public_url . 'js/jquery.barrating.min.js',
				array( 'jquery' ),
				'1.2.1'
			);

			// register custom js
			wp_enqueue_script(
				'stars-rating-script',
				$plugin_public_url . 'js/script.js',
				array( 'jquery' ),
				'1.0.0'
			);
		}
	}

endif;
