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

		public function load_admin_files() { // TODO: define the path of the directories first like URLs.
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/stars-rating-settings.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/stars-rating-metabox.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/stars-rating-comments-column.php';
		}

		public function load_public_files() {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/stars-rating-public.php';
		}

		public function enqueue_admin_scripts() {
			// fontawesome
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

			$stars_style = sanitize_html_class( get_option( 'stars_style', 'regular' ) );
			$output      = '<span class="rating-stars">';

			if ( ! empty( $rating ) ) {

				for ( $count = 1; $count <= $rating; $count++ ) {
					$output .= "<i class='fa stars-style-{$stars_style} rated'></i>";
				}

				$unrated = 5 - $rating;
				for ( $count = 1; $count <= $unrated; $count++ ) {
					$output .= "<i class='fa stars-style-{$stars_style}'></i>";
				}
			} else {
				for ( $count = 1; $count <= 5; $count++ ) {
					$output .= "<i class='fa stars-style-{$stars_style}'></i>";
				}
			}

			$output .= '</span>';


			return $output;
		}
	}
}