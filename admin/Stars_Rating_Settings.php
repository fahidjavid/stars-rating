<?php
/**
 * This file is responsible for the Stars Rating plugin settings page on admin side.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Stars_Rating_Settings' ) ) {
	/**
	 * Stars Rating Settings
	 */
	class Stars_Rating_Settings {

		public function __construct() {
			// Hook to add the settings page to the WordPress admin menu
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			// Hook to register the plugin settings
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		public function add_settings_page() {

			add_menu_page(
				esc_html__( 'Stars Rating Settings', 'stars-rating' ),  // Page title
				esc_html__( 'Stars Rating', 'stars-rating' ),           // Menu title
				'manage_options',                                       // Capability required to access the page
				'stars-rating-settings',                                // Menu slug
				array( $this, 'settings_page_content' ),                // Callback function to display the settings page content
				'dashicons-star-filled',                                // Icon for the menu item (optional)
				80                                                       // Position of the menu item (optional)
			);
		}

		public function settings_page_content() {
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Stars Rating Settings', 'stars-rating' ); ?></h1>
				<form method="post" action="options.php">
					<?php
					// Output security fields for the registered setting "stars_rating_settings"
					settings_fields( 'stars_rating_settings' );
					// Output setting sections and their fields
					do_settings_sections( 'stars-rating-settings' );
					// Output save settings button with escaping
					submit_button( esc_html__( 'Save Settings', 'stars-rating' ) );
					?>
				</form>
			</div>
			<?php
		}

		public function register_settings() {
			// Register a new setting group and fields
			register_setting( 'stars_rating_settings', 'enabled_post_types' );
			register_setting( 'stars_rating_settings', 'require_rating' );
			register_setting( 'stars_rating_settings', 'avg_rating_display' );
			register_setting( 'stars_rating_settings', 'stars_style' );
			register_setting( 'stars_rating_settings', 'google_search_stars' );
			register_setting( 'stars_rating_settings', 'google_search_stars_type' );
			register_setting( 'stars_rating_settings', 'sr_negative_rating_alert' );
			register_setting( 'stars_rating_settings', 'sr_negative_rating_threshold' );
			register_setting( 'stars_rating_settings', 'sr_negative_rating_contact_url' );

			// Add a new section to the settings page
			add_settings_section(
				'stars_rating_settings_section',
				esc_html__( 'General Settings', 'stars-rating' ),  // Section title with escaping
				array( $this, 'settings_section_callback' ),
				'stars-rating-settings'
			);

            // Add enabled post types field
			add_settings_field(
				'enabled_post_types',
				esc_html__( 'Enabled Post Types', 'stars-rating' ),
				array( $this, 'enabled_post_types_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'enabled_post_types' )
			);

			// Add required rating selection field
			add_settings_field(
				'require_rating',
				esc_html__( 'Require Rating Selection', 'stars-rating' ),
				array( $this, 'require_rating_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'require_rating' )
			);

			add_settings_field(
				'avg_rating_display',
				esc_html__( 'Average Rating Above Comments Section', 'stars-rating' ),
				array( $this, 'avg_rating_display_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'avg_rating_display' )
			);

			// Add stars style field
			add_settings_field(
				'stars_style',
				esc_html__( 'Stars Style', 'stars-rating' ),
				array( $this, 'stars_style_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'stars_style' )
			);

			// Add stars rating in Google search results field
			add_settings_field(
				'google_search_stars',
				esc_html__( 'Stars Rating In Google Search Results', 'stars-rating' ),
				array( $this, 'google_search_stars_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'google_search_stars' )
			);

			// Add type of reviews in Google search results field
			add_settings_field(
				'google_search_stars_type',
				esc_html__( 'Type of Reviews In Google Search Results', 'stars-rating' ),
				array( $this, 'google_search_stars_type_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'google_search_stars_type' )
			);

			// Add enable negative rating alert
			add_settings_field(
				'sr_negative_rating_alert',
				esc_html__( 'Enable Negative Rating Alert', 'stars-rating' ),
				array( $this, 'negative_rating_alert_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'sr_negative_rating_alert' )
			);

			// Add negative rating threshold for alert field
			add_settings_field(
				'sr_negative_rating_threshold',
				esc_html__( 'Negative Rating Threshold for Alert', 'stars-rating' ),
				array( $this, 'negative_rating_threshold_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'sr_negative_rating_threshold' )
			);

			// Add contact before negative rating url field
			add_settings_field(
				'sr_negative_rating_contact_url',
				esc_html__( 'Contact Before Negative Rating URL', 'stars-rating' ),
				array( $this, 'negative_rating_contact_url_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'sr_negative_rating_contact_url' )
			);

			// Add donation to stars rating plugin field
			add_settings_field(
				'donation_link',
				esc_html__( 'Donate "Stars Rating" And Similar OpenSource Projects!', 'stars-rating' ),
				array( $this, 'donation_link_callback' ),
				'stars-rating-settings',
				'stars_rating_settings_section',
				array( 'donation_link' )
			);
		}

		public function settings_section_callback() {
			esc_html_e( 'Adjust the general settings for the Stars Rating plugin.', 'stars-rating' );
		}

		public function enabled_post_types_callback( $args ) {

			$enabled_posts = get_option( 'enabled_post_types', array( 'post', 'page' ) );

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
            <p class="description"><?php esc_html_e( 'For example: Product, Recipe, Book, Course etc.', 'stars-rating' ) ?> <a href="https://developers.google.com/search/docs/appearance/structured-data/review-snippet" target="_blank"><?php esc_html_e('For more details click here.', 'stars-rating'); ?></a></p>
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
	}

	// Initialize the settings page
	new Stars_Rating_Settings();
}
