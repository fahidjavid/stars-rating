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
		 * @since 1.0.0
		 * @var Stars_Rating_Settings
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
//			add_action( 'init', array( $this, 'update_settings_field' ) );
		}

		public function stars_rating_section() {

			add_settings_section(
				'stars_rating_section',
				esc_html__( 'Stars Rating', 'stars-rating' ),
				array( $this, 'stars_rating_section_callback' ),
				'discussion',
				array(
					'before_section' => '<div style="background: #80808021; padding: 20px; border-radius: 10px;">',
					'after_section'  => '</div>',
					'section_class'  => 'stars-rating-settings', // TODO: this class is not applying, it can be WP contribution to fix.
				)
			);

			add_settings_field(
				'enabled_post_types',
				esc_html__( 'Enabled Post Types', 'stars-rating' ),
				array( $this, 'enabled_post_types_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'enabled_post_types' )
			);

			add_settings_field(
				'require_rating',
				esc_html__( 'Require Rating Selection', 'stars-rating' ),
				array( $this, 'require_rating_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'require_rating' )
			);

			add_settings_field(
				'avg_rating_display',
				esc_html__( 'Average Rating Above Comments Section', 'stars-rating' ),
				array( $this, 'avg_rating_display_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'avg_rating_display' )
			);

			add_settings_field(
				'stars_style',
				esc_html__( 'Stars Style', 'stars-rating' ),
				array( $this, 'stars_style_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'stars_style' )
			);

			add_settings_field(
				'google_search_stars',
				esc_html__( 'Stars Rating In Google Search Results', 'stars-rating' ),
				array( $this, 'google_search_stars_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'google_search_stars' )
			);

			add_settings_field(
				'google_search_stars_type',
				esc_html__( 'Type of Reviews In Google Search Results', 'stars-rating' ),
				array( $this, 'google_search_stars_type_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'google_search_stars_type' )
			);

			add_settings_field(
				'sr_negative_rating_alert',
				esc_html__( 'Enable Negative Rating Alert', 'stars-rating' ),
				array( $this, 'negative_rating_alert_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'sr_negative_rating_alert' )
			);

			add_settings_field(
				'sr_negative_rating_threshold',
				esc_html__( 'Negative Rating Threshold for Alert', 'stars-rating' ),
				array( $this, 'negative_rating_threshold_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'sr_negative_rating_threshold' )
			);

			add_settings_field(
				'sr_negative_rating_contact_url',
				esc_html__( 'Contact Before Negative Rating URL', 'stars-rating' ),
				array( $this, 'negative_rating_contact_url_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'sr_negative_rating_contact_url' )
			);

			add_settings_field(
				'donation_link',
				esc_html__( 'Donate "Stars Rating" And Similar OpenSource Projects!', 'stars-rating' ),
				array( $this, 'donation_link_callback' ),
				'discussion',
				'stars_rating_section',
				array( 'donation_link' )
			);

			// register enabled_posts field
			register_setting( 'discussion', 'enabled_post_types', 'esc_attr' );

			// register require_rating field
			register_setting( 'discussion', 'require_rating', 'esc_attr' );

			// register avg_rating_display field
			register_setting( 'discussion', 'avg_rating_display', 'esc_attr' );

			// register stars_style field
			register_setting( 'discussion', 'stars_style', 'esc_attr' );

			// register google_search_stars field
			register_setting( 'discussion', 'google_search_stars', 'esc_attr' );

			// register google_search_stars_type field
			register_setting( 'discussion', 'google_search_stars_type', 'esc_attr' );

            // register negative rating fields
			register_setting( 'discussion', 'sr_negative_rating_alert', 'esc_attr' );
			register_setting( 'discussion', 'sr_negative_rating_threshold', 'intVal' );
			register_setting( 'discussion', 'sr_negative_rating_contact_url', 'esc_url' );
		}

		public function stars_rating_section_callback() {
			echo '<p class="description">' . esc_html__( 'Check the post types on which you want to enable stars rating feature.', 'stars-rating' ) . '</p>';
		}

		public function enabled_post_types_callback( $args ) {

			$enabled_posts = get_option( 'enabled_post_types', array( 'post', 'page' ) );

			if ( ! is_array( $enabled_posts ) ) {
				$enabled_posts = (array)$enabled_posts;
			}

			$query = array(
				'public' => true
			);

			// get publicly registered post types
			$post_types = get_post_types( $query, 'names' );

			foreach ( $post_types as $post_type ) {

				$checked = in_array( $post_type, $enabled_posts ) ? 'checked="checked"' : '';
				echo '<label for="' . esc_attr( $post_type ) . '"><input type="checkbox" id="' . esc_attr( $post_type ) . '" name="' . esc_attr( $args[0] ) . '[]" value="' . esc_attr( $post_type ) . '" ' . esc_html( $checked ) . '/>' . esc_html( ucwords( $post_type ) ) . '</label><br>';
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

			echo '<label for="avg_rating_display"><input type="radio" id="avg_rating_display" name="avg_rating_display" value="show" ' . esc_html( $avg_rating_show_status ) . ' />' . esc_html__( 'Show', 'stars-rating' ) . '</label>';
			echo '<label for="avg_rating_display_no"><input type="radio" id="avg_rating_display_no" name="avg_rating_display" value="hide" ' . esc_html( $avg_rating_hide_status ) . ' />' . esc_html__( 'Hide', 'stars-rating' ) . '</label>';
		}

		public function require_rating_callback() {
			$require_rating_selection = get_option( 'require_rating', 'no' );

			$require_rating    = 'unchecked';
			$require_rating_no = 'checked';

			if ( 'yes' == $require_rating_selection ) {
				$require_rating    = 'checked';
				$require_rating_no = 'unchecked';
			}

			echo '<label for="require_rating"><input type="radio" id="require_rating" name="require_rating" value="yes" ' . esc_html( $require_rating ) . ' />' . esc_html__( 'Yes', 'stars-rating' ) . '</label>';
			echo '<label for="require_rating_no"><input type="radio" id="require_rating_no" name="require_rating" value="no" ' . esc_html( $require_rating_no ) . ' />' . esc_html__( 'No', 'stars-rating' ) . '</label>';
		}

		public function stars_style_callback() {

			$stars_style = get_option( 'stars_style', 'regular' );

			$stars_style_regular = 'checked';
			$stars_style_solid   = 'unchecked';

			if ( 'solid' == $stars_style ) {
				$stars_style_solid   = 'checked';
				$stars_style_regular = 'unchecked';
			}

			echo '<label for="stars_style_regular"><input type="radio" id="stars_style_regular" name="stars_style" value="regular" ' . esc_html( $stars_style_regular ) . ' />' . esc_html__( 'Regular', 'stars-rating' ) . '</label>';
			echo '<label for="stars_style_solid"><input type="radio" id="stars_style_solid" name="stars_style" value="solid" ' . esc_html( $stars_style_solid ) . ' />' . esc_html__( 'Solid', 'stars-rating' ) . '</label>';
		}

		public function google_search_stars_callback() {

			$google_search_stars = get_option( 'google_search_stars', 'show' );

			$google_search_stars_show = 'checked';
			$google_search_stars_hide = 'unchecked';

			if ( 'hide' == $google_search_stars ) {
				$google_search_stars_hide = 'checked';
				$google_search_stars_show = 'unchecked';
			}

			echo '<label for="google_search_stars_show"><input type="radio" id="google_search_stars_show" name="google_search_stars" value="show" ' . esc_html( $google_search_stars_show ) . ' />' . esc_html__( 'Show', 'stars-rating' ) . '</label>';
			echo '<label for="google_search_stars_hide"><input type="radio" id="google_search_stars_hide" name="google_search_stars" value="hide" ' . esc_html( $google_search_stars_hide ) . ' />' . esc_html__( 'Hide', 'stars-rating' ) . '</label>';
		}

		public function negative_rating_alert_callback() {

			$negative_rating_alert = get_option( 'sr_negative_rating_alert', 'disable' );

			$negative_rating_alert_enabled  = 'checked';
			$negative_rating_alert_disabled = 'unchecked';

			if ( 'disable' == $negative_rating_alert ) {
				$negative_rating_alert_enabled  = 'unchecked';
				$negative_rating_alert_disabled = 'checked';
			}

			echo '<label for="negative_rating_alert_enable"><input type="radio" id="negative_rating_alert_enable" name="sr_negative_rating_alert" value="enable" ' . esc_html( $negative_rating_alert_enabled ) . ' />' . esc_html__( 'Enable', 'stars-rating' ) . '</label>';
			echo '<label for="negative_rating_alert_disable"><input type="radio" id="negative_rating_alert_disable" name="sr_negative_rating_alert" value="disable" ' . esc_html( $negative_rating_alert_disabled ) . ' />' . esc_html__( 'Disable', 'stars-rating' ) . '</label>';
		}

		public function google_search_stars_type_callback() {

			$google_search_stars_type = get_option( 'google_search_stars_type' );

			echo '<input type="text" id="google_search_stars_type" name="google_search_stars_type" value="' . esc_attr( $google_search_stars_type ) . '" />';
			?>
            <p class="description"><?php esc_html_e( 'For example: Product, Service, Brand, Event', 'stars-rating' ) ?></p>
			<?php
		}

		public function negative_rating_threshold_callback() {

			$negative_rating_threshold = get_option( 'sr_negative_rating_threshold' );

			echo '<input type="text" id="negative_rating_threshold" name="sr_negative_rating_threshold" value="' . esc_attr( $negative_rating_threshold ) . '" />';
			?>
            <p class="description"><?php esc_html_e( 'For example: 3', 'stars-rating' ) ?></p>
            <p class="description"><?php esc_html_e( 'Alert will be displayed if rating is selected equal or below the given threshold number.', 'stars-rating' ) ?></p>
			<?php
		}

		public function negative_rating_contact_url_callback() {

			$negative_rating_contact_url = get_option( 'sr_negative_rating_contact_url' );

			echo '<input type="text" id="negative_rating_contact_url" name="sr_negative_rating_contact_url" value="' . esc_attr( $negative_rating_contact_url ) . '" />';
			?>
            <p class="description"><?php esc_html_e( 'For example: your website contact page url', 'stars-rating' ) ?></p>
			<?php
		}

		public function donation_link_callback() {
			echo '<div class="custom-links"><a class="donation-link" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=fahidjavid%40gmail.com&item_name=OpenSource+Projects+Support&currency_code=USD&source=url" target="_blank">Buy Me A Coffee!</a><i>OR</i><a class="review-link" href="https://wordpress.org/support/plugin/stars-rating/reviews/#new-post" target="_blank">Review Plugin!</a></div>';
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
			add_filter( 'pre_update_option_stars_style', array(
				$this,
				'update_field_stars_style'
			), 10, 2 );
			add_filter( 'pre_update_option_google_search_stars', array(
				$this,
				'update_field_google_search_stars'
			), 10, 2 );
			add_filter( 'pre_update_option_google_search_stars_type', array(
				$this,
				'update_field_google_search_stars_type'
			), 10, 2 );
			add_filter( 'pre_update_option_sr_negative_rating_alert', array(
				$this,
				'update_field_sr_negative_rating_alert'
			), 10, 2 );
		}

		public function update_field_enabled_post_types( $new_value, $old_value ) {

			$post_types = isset( $_POST['enabled_post_types'] ) ? (array)$_POST['enabled_post_types'] : array(
				'post',
				'page'
			);
			$post_types = array_map( 'sanitize_text_field', $post_types );

			return $post_types;
		}

		public function update_field_require_rating( $new_value, $old_value ) {
			$new_value = sanitize_text_field( $_POST['require_rating'] );

			return $new_value;
		}

		public function update_field_avg_rating_display( $new_value, $old_value ) {
			$new_value = sanitize_text_field( $_POST['avg_rating_display'] );

			return $new_value;
		}

		public function update_field_stars_style( $new_value, $old_value ) {
			$new_value = sanitize_text_field( $_POST['stars_style'] );

			return $new_value;
		}

		public function update_field_google_search_stars( $new_value, $old_value ) {
			$new_value = sanitize_text_field( $_POST['google_search_stars'] );

			return $new_value;
		}

		public function update_field_google_search_stars_type( $new_value, $old_value ) {
			$new_value = sanitize_text_field( $_POST['google_search_stars_type'] );

			return $new_value;
		}

		public function update_field_sr_negative_rating_alert( $new_value, $old_value ) {
			$new_value = sanitize_text_field( $_POST['sr_negative_rating_alert'] );

			return $new_value;
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
