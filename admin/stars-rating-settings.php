<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Stars_Rating_Settings' ) ) :

	/**
	 * Class Stars_Rating_Settings
	 *
	 * Plugin's settings class
	 *
	 * @since 1.0.0
	 */
	final class Stars_Rating_Settings {

		/**
		 * Single instance of Class.
		 *
		 * @var Stars_Rating_Settings
		 * @since 1.0.0
		 */
		protected static $_instance;

		/**
		 * Provides singleton instance.
		 *
		 * @since 1.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Stars_Rating_Settings constructor.
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->init_hooks();

			// Stars Rating plugin settings loaded action hook
			do_action( 'Stars_Rating_Settings_loaded' );

		}

		public function init_hooks() {

			add_action( 'admin_init', array( $this, 'stars_rating_section' ) );
			add_action( 'init', array( $this, 'update_settings_field' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_plugin_files' ) );
		}

		public function stars_rating_section() {

			add_settings_section(
				'stars_rating_section',
				esc_html__( 'Stars Rating', 'stars-rating' ),
				array( $this, 'stars_rating_section_callback' ),
				'discussion'
			);

			add_settings_field(
				'enabled_post_types',
				esc_html__( 'Enabled Post Types', 'stars-rating' ),
				array( $this, 'enabled_post_types_callback' ),
				'discussion',
				'stars_rating_section',
				array(
					'enabled_post_types'
				)
			);

			add_settings_field( 'require_rating',
				esc_html__( 'Require Rating Selection', 'stars-rating' ),
				array( $this, 'require_rating_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'require_rating' ) );

			add_settings_field( 'avg_rating_display',
				esc_html__( 'Average Rating Above Comments Section', 'stars-rating' ),
				array( $this, 'avg_rating_display_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'avg_rating_display' ) );

			// register enabled_posts field
			register_setting( 'discussion', 'enabled_post_types', 'esc_attr' );

			// register require_rating field
			register_setting( 'discussion', 'require_rating', 'esc_attr' );

			// register avg_rating_display field
			register_setting( 'discussion', 'avg_rating_display', 'esc_attr' );
		}

		public function stars_rating_section_callback() {
			echo '<p class="description">' . esc_html__( 'Check the post types on which you want to enable stars rating feature.', 'stars-rating' ) . '</p>';
		}

		public function enabled_post_types_callback( $args ) {

			$enabled_posts = get_option( ' enabled_post_types' );

			if ( ! is_array( $enabled_posts ) ) {
				$enabled_posts = (array) $enabled_posts;
			}

			$query = array(
				'public' => true
			);

			// get publicly registered post types
			$post_types = get_post_types( $query, 'names' );

			foreach ( $post_types as $post_type ) {

				$checked = in_array( $post_type, $enabled_posts ) ? 'checked="checked"' : '';
				echo '<label for="' . $post_type . '"><input type="checkbox" id="' . $post_type . '" name="' . $args[0] . '[]" value="' . $post_type . '" ' . $checked . '/>' . ucwords( $post_type ) . '</label><br>';
			}

		}

		public function avg_rating_display_callback() {
			$avg_rating_display = get_option( ' avg_rating_display', 'show' );

			$avg_rating_show_status = 'checked';
			$avg_rating_hide_status = 'unchecked';

			if ( 'hide' == $avg_rating_display ) {
				$avg_rating_show_status = 'unchecked';
				$avg_rating_hide_status = 'checked';
			}

			echo '<label for="avg_rating_display"><input type="radio" id="avg_rating_display" name="avg_rating_display" value="show" ' . $avg_rating_show_status . ' />' . esc_html__( 'Show', 'stars-rating' ) . '</label>';
			echo '<label for="avg_rating_display_no"><input type="radio" id="avg_rating_display_no" name="avg_rating_display" value="hide" ' . $avg_rating_hide_status . ' />' . esc_html__( 'Hide', 'stars-rating' ) . '</label>';
		}

		public function require_rating_callback() {
			$require_rating_selection = get_option( 'require_rating', 'no' );

			$require_rating = 'unchecked';
			$require_rating_no = 'checked';

			if ( 'yes' == $require_rating_selection ) {
				$require_rating = 'checked';
				$require_rating_no = 'unchecked';
			}

			echo '<label for="require_rating"><input type="radio" id="require_rating" name="require_rating" value="yes" ' . $require_rating . ' />' . esc_html__( 'Yes', 'stars-rating' ) . '</label>';
			echo '<label for="require_rating_no"><input type="radio" id="require_rating_no" name="require_rating" value="no" ' . $require_rating_no . ' />' . esc_html__( 'No', 'stars-rating' ) . '</label>';
		}

		public function update_settings_field() {
			add_filter( 'pre_update_option_enabled_post_types', array(
				$this,
				'update_field_enabled_post_types'
			), 10, 2 );
			add_filter( 'pre_update_option_require_rating', array(
				$this,
				'update_field_require_rating'
			), 10, 2 );
			add_filter( 'pre_update_option_avg_rating_display', array(
				$this,
				'update_field_avg_rating_display'
			), 10, 2 );
		}

		public function update_field_enabled_post_types( $new_value, $old_value ) {
			$new_value = $_POST['enabled_post_types'];
			return $new_value;
		}

		public function update_field_require_rating( $new_value, $old_value ) {
			$new_value = $_POST['require_rating'];
			return $new_value;
		}

		public function update_field_avg_rating_display( $new_value, $old_value ) {
			$new_value = $_POST['avg_rating_display'];
			return $new_value;
		}

		public function enqueue_plugin_files() {

			$plugin_url = WP_PLUGIN_URL;
			$plugin_admin_url = $plugin_url . '/stars-rating/admin/';

			// stars rating admin
			wp_enqueue_style(
				'stars-rating-admin',
				$plugin_admin_url . 'css/stars-rating-admin.css',
				array(),
				'1.0.0'
			);
		}

	}

endif;


/**
 * Main instance of Stars_Rating_Settings.
 *
 * Returns the main instance of Stars_Rating_Settings to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Stars_Rating_Settings
 */
function Stars_Rating_Settings() {
	return Stars_Rating_Settings::instance();
}

// Get Stars_Rating_Settings Running.
Stars_Rating_Settings();