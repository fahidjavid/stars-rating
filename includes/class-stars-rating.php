<?php
/**
 * This class is responsible for managing both public and admin stuff.
 */
if ( ! class_exists( 'Stars_Rating' ) ) {
	/**
	 * Class to manage both front-facing and admin-facing assets and functionality.
	 */
	class Stars_Rating {

		/**
		 * Single instance of Class.
		 *
		 * @since 4.0.0
		 * @var Stars_Rating
		 */
		protected static $_instance;

		public function __construct() {

			$this->init_hooks();
			$this->load_files();
		}

		public function init_hooks() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_likes_scripts' ) );
		}

		public function load_files() {
			// Load Admin Files.
			if ( is_admin() ) {
				$this->load_admin_files();
			}

			//Load Public Files.
			if ( ! is_admin() ) {
				$this->load_public_files();
			}

			// The likes file must be loaded on every request (including admin-ajax.php)
			// so its wp_ajax_* handlers are always registered.
			if ( 'enable' === get_option( 'sr_likes_enabled', 'disable' ) ) {
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/stars-rating-likes.php';
			}
		}

		/**
		 * Provides singleton instance.
		 *
		 * @since 4.0.0
		 * @return self instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function load_admin_files() {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/Stars_Rating_Settings.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/stars-rating-metabox.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/stars-rating-comments-column.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/stars-rating-posts-column.php';
		}

		public function load_public_files() {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/stars-rating-public.php';
		}

		public function enqueue_admin_scripts( $hook ) {
			// fontawesome (needed on all admin pages for the comments column stars)
			wp_enqueue_style(
				'fontawesome',
				PLUGIN_INCLUDE_URL . 'css/font-awesome.min.css',
				array(),
				'4.7.0'
			);

			// stars rating admin
			wp_enqueue_style(
				'stars-rating-admin',
				PLUGIN_ADMIN_URL . 'css/stars-rating-admin.css',
				array(),
				'1.0.0'
			);

			// wp-color-picker — only on the plugin settings page
			if ( 'toplevel_page_stars-rating-settings' === $hook ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_add_inline_script(
					'wp-color-picker',
					'jQuery(document).ready(function($){ $(".sr-color-picker").wpColorPicker(); });'
				);
			}
		}

		public function enqueue_public_scripts() {
			if ( ! Stars_Rating_Public::status() ) {
				return;
			}

			// fontawesome
			wp_enqueue_style(
				'fontawesome',
				PLUGIN_INCLUDE_URL . 'css/font-awesome.min.css',
				array(),
				'4.7.0'
			);

			// bar rating theme
			wp_enqueue_style(
				'bar-rating-theme',
				PLUGIN_PUBLIC_URL . 'css/fontawesome-stars.css',
				array(),
				'2.6.3'
			);

			// plugin css
			wp_enqueue_style(
				'stars-rating-public',
				PLUGIN_PUBLIC_URL . 'css/stars-rating-public.css',
				array(),
				'1.0.0'
			);

			// bar rating
			wp_enqueue_script(
				'bar-rating',
				PLUGIN_PUBLIC_URL . 'js/jquery.barrating.min.js',
				array( 'jquery' ),
				'1.2.1'
			);

			// register custom js
			wp_enqueue_script(
				'stars-rating-script',
				PLUGIN_PUBLIC_URL . 'js/script.js',
				array( 'jquery' ),
				'1.0.0'
			);

			wp_localize_script( 'stars-rating-script', 'srRatingVars', array(
				'requireMsg' => esc_html( get_option( 'sr_str_require_rating', __( 'Please select a star rating before submitting your review.', 'stars-rating' ) ) ),
			) );

			// Inject the custom star color as inline CSS so it overrides the stylesheet default.
			$stars_color = sanitize_hex_color( get_option( 'stars_color', '#EDB867' ) );
			if ( ! $stars_color ) {
				$stars_color = '#EDB867';
			}
			$inline_css = "
				.rating-stars i.rated:after,
				#stars-rating-review .br-widget a.br-active:after,
				#stars-rating-review .br-widget a.br-selected:after {
					color: {$stars_color};
				}
			";
			wp_add_inline_style( 'stars-rating-public', $inline_css );
		}

		/**
		 * Enqueue likes/dislikes scripts and styles on singular posts of enabled types.
		 */
		public function enqueue_likes_scripts(): void {

			if ( 'enable' !== get_option( 'sr_likes_enabled', 'disable' ) ) {
				return;
			}

			if ( ! is_singular() ) {
				return;
			}

			$enabled = (array) get_option( 'sr_likes_post_types', array( 'post', 'page' ) );
			if ( ! in_array( get_post_type(), $enabled, true ) ) {
				return;
			}

			wp_enqueue_style(
				'stars-rating-likes',
				PLUGIN_PUBLIC_URL . 'css/stars-rating-likes.css',
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'stars-rating-likes',
				PLUGIN_PUBLIC_URL . 'js/likes.js',
				array( 'jquery' ),
				'1.0.0',
				true
			);

			wp_localize_script( 'stars-rating-likes', 'srLikesVars', array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'loginUrl'     => wp_login_url( get_permalink() ),
				'mustLoginMsg' => esc_html( get_option( 'sr_str_likes_must_login', __( 'You must be logged in to vote.', 'stars-rating' ) ) ),
				'thanksMsg'    => esc_html( get_option( 'sr_str_likes_thanks', __( 'Thanks for the feedback!', 'stars-rating' ) ) ),
			) );
		}

		/**
		 * A common public helper function to generate stars based on the given rating number.
		 *
		 * @param $rating
		 *
		 * @return string
		 */
		public static function get_rating_stars_markup( $rating ) {

			$rating = absint( $rating > 0 ? round( $rating ) : $rating );

			// Put the style class on the wrapper span, not on individual <i> elements.
			// This mirrors how the comment-form widget handles it and allows a clean
			// descendant CSS selector (.rating-stars.stars-style-solid i:after).
			$stars_style = sanitize_html_class( get_option( 'stars_style', 'regular' ) );
			$output      = '<span class="rating-stars stars-style-' . $stars_style . '">';

			if ( ! empty( $rating ) ) {

				for ( $count = 1; $count <= $rating; $count++ ) {
					$output .= '<i class="fa rated"></i>';
				}

				$unrated = 5 - $rating;
				for ( $count = 1; $count <= $unrated; $count++ ) {
					$output .= '<i class="fa"></i>';
				}
			} else {
				for ( $count = 1; $count <= 5; $count++ ) {
					$output .= '<i class="fa"></i>';
				}
			}

			$output .= '</span>';


			return $output;
		}
	}
}