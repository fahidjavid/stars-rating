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
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		public function add_settings_page() {
			add_menu_page(
				esc_html__( 'Stars Rating Settings', 'stars-rating' ),
				esc_html__( 'Stars Rating', 'stars-rating' ),
				'manage_options',
				'stars-rating-settings',
				array( $this, 'settings_page_content' ),
				'dashicons-star-filled',
				80
			);
		}

		public function settings_page_content() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'stars-rating' ) );
			}
			?>
			<div class="sr-wrap">

				<!-- Header -->
				<div class="sr-header">
					<div class="sr-logo">
						<span class="dashicons dashicons-star-filled"></span>
						<div class="sr-logo-text">
							<h1><?php esc_html_e( 'Stars Rating', 'stars-rating' ); ?></h1>
							<span><?php esc_html_e( 'Plugin Settings', 'stars-rating' ); ?></span>
						</div>
					</div>
					<div class="sr-header-links">
						<a href="https://wordpress.org/support/plugin/stars-rating/reviews/#new-post" target="_blank" class="sr-btn sr-btn-review">
							★ <?php esc_html_e( 'Leave a Review', 'stars-rating' ); ?>
						</a>
					</div>
				</div>

				<!-- Success notice -->
				<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) : ?>
					<div class="sr-notice sr-notice-success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Settings saved successfully.', 'stars-rating' ); ?>
					</div>
				<?php endif; ?>

				<form method="post" action="options.php">
					<?php settings_fields( 'stars_rating_settings' ); ?>

					<div class="sr-grid">

						<!-- General Card -->
						<div class="sr-card">
							<div class="sr-card-header">
								<span class="dashicons dashicons-admin-generic"></span>
								<h2><?php esc_html_e( 'General', 'stars-rating' ); ?></h2>
							</div>
							<div class="sr-card-body">

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Enabled Post Types', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Select the post types where star ratings are available.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_post_types_checkboxes(); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Require Rating', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Force users to select a rating before submitting a comment.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'require_rating', 'no', array(
											'yes' => esc_html__( 'Yes', 'stars-rating' ),
											'no'  => esc_html__( 'No', 'stars-rating' ),
										) ); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Stars Style', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Choose the visual style of rating stars.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_stars_style_field(); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Stars Color', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Pick a color for rated stars to match your brand.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<input
											type="text"
											name="stars_color"
											class="sr-color-picker"
											value="<?php echo esc_attr( get_option( 'stars_color', '#EDB867' ) ); ?>"
											data-default-color="#EDB867"
										/>
									</div>
								</div>

							</div>
						</div>

						<!-- Display Card -->
						<div class="sr-card">
							<div class="sr-card-header">
								<span class="dashicons dashicons-visibility"></span>
								<h2><?php esc_html_e( 'Display', 'stars-rating' ); ?></h2>
							</div>
							<div class="sr-card-body">

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Average Rating', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Show the average star rating above the comments section.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'avg_rating_display', 'show', array(
											'show' => esc_html__( 'Show', 'stars-rating' ),
											'hide' => esc_html__( 'Hide', 'stars-rating' ),
										) ); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Shortcode', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Use this shortcode to display the average rating anywhere.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<code class="sr-shortcode">[stars_rating_avg]</code>
									</div>
								</div>

							</div>
						</div>

						<!-- Google Rich Snippets Card -->
						<div class="sr-card">
							<div class="sr-card-header">
								<span class="dashicons dashicons-search"></span>
								<h2><?php esc_html_e( 'Google Rich Snippets', 'stars-rating' ); ?></h2>
							</div>
							<div class="sr-card-body">

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Stars in Search Results', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Output JSON-LD structured data for Google\'s rating snippets.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'google_search_stars', 'show', array(
											'show' => esc_html__( 'Enable', 'stars-rating' ),
											'hide' => esc_html__( 'Disable', 'stars-rating' ),
										) ); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Review Type', 'stars-rating' ); ?></strong>
										<p class="sr-desc">
											<?php esc_html_e( 'e.g. Product, Recipe, Book, Course.', 'stars-rating' ); ?>
											<a href="https://developers.google.com/search/docs/appearance/structured-data/review-snippet" target="_blank"><?php esc_html_e( 'Learn more ↗', 'stars-rating' ); ?></a>
										</p>
									</div>
									<div class="sr-field-input">
										<input
											type="text"
											id="google_search_stars_type"
											name="google_search_stars_type"
											class="sr-input"
											value="<?php echo esc_attr( get_option( 'google_search_stars_type', '' ) ); ?>"
											placeholder="<?php esc_attr_e( 'e.g. Product', 'stars-rating' ); ?>"
										/>
									</div>
								</div>

							</div>
						</div>

						<!-- Negative Rating Alert Card -->
						<div class="sr-card">
							<div class="sr-card-header">
								<span class="dashicons dashicons-warning"></span>
								<h2><?php esc_html_e( 'Negative Rating Alert', 'stars-rating' ); ?></h2>
							</div>
							<div class="sr-card-body">

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Enable Alert', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Show a popup when a reviewer leaves a low star rating.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'sr_negative_rating_alert', 'disable', array(
											'enable'  => esc_html__( 'Enable', 'stars-rating' ),
											'disable' => esc_html__( 'Disable', 'stars-rating' ),
										) ); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Rating Threshold', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Alert fires when rating is at or below this value (1–5).', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<input
											type="number"
											id="sr_negative_rating_threshold"
											name="sr_negative_rating_threshold"
											class="sr-input sr-input-number"
											value="<?php echo esc_attr( get_option( 'sr_negative_rating_threshold', '' ) ); ?>"
											min="1"
											max="5"
											step="1"
											placeholder="3"
										/>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Contact Page URL', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Direct unhappy reviewers here before they post a negative review.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<input
											type="url"
											id="sr_negative_rating_contact_url"
											name="sr_negative_rating_contact_url"
											class="sr-input"
											value="<?php echo esc_attr( get_option( 'sr_negative_rating_contact_url', '' ) ); ?>"
											placeholder="https://example.com/contact"
										/>
									</div>
								</div>

							</div>
						</div>

					</div><!-- .sr-grid -->

					<div class="sr-actions">
						<?php submit_button( esc_html__( 'Save Settings', 'stars-rating' ), 'primary', 'submit', false ); ?>
					</div>

				</form>
			</div><!-- .sr-wrap -->
			<?php
		}

		public function register_settings() {
			register_setting( 'stars_rating_settings', 'enabled_post_types', array(
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
			) );
			register_setting( 'stars_rating_settings', 'require_rating', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );
			register_setting( 'stars_rating_settings', 'avg_rating_display', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );
			register_setting( 'stars_rating_settings', 'stars_style', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );
			register_setting( 'stars_rating_settings', 'google_search_stars', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );
			register_setting( 'stars_rating_settings', 'google_search_stars_type', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );
			register_setting( 'stars_rating_settings', 'sr_negative_rating_alert', array(
				'sanitize_callback' => 'sanitize_text_field',
			) );
			register_setting( 'stars_rating_settings', 'sr_negative_rating_threshold', array(
				'sanitize_callback' => 'absint',
			) );
			register_setting( 'stars_rating_settings', 'sr_negative_rating_contact_url', array(
				'sanitize_callback' => 'esc_url_raw',
			) );
			register_setting( 'stars_rating_settings', 'stars_color', array(
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#EDB867',
			) );
		}

		/**
		 * Sanitize the enabled post types array.
		 */
		public function sanitize_post_types( $value ) {
			if ( ! is_array( $value ) ) {
				return array();
			}
			return array_map( 'sanitize_key', $value );
		}

		/**
		 * Render a group of radio buttons styled as pill toggles.
		 *
		 * @param string $option_name  The option/input name.
		 * @param string $default      Default value.
		 * @param array  $choices      Array of value => label pairs.
		 */
		private function render_radio_field( $option_name, $default, $choices ) {
			$current = get_option( $option_name, $default );
			echo '<div class="sr-radio-group">';
			foreach ( $choices as $value => $label ) {
				$id = 'sr_' . $option_name . '_' . $value;
				echo '<label>';
				echo '<input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $value ) . '"';
				checked( $current, $value );
				echo ' />';
				echo '<span>' . esc_html( $label ) . '</span>';
				echo '</label>';
			}
			echo '</div>';
		}

		/**
		 * Render post type checkboxes.
		 */
		private function render_post_types_checkboxes() {
			$enabled    = get_option( 'enabled_post_types', array( 'post', 'page' ) );
			$enabled    = is_array( $enabled ) ? $enabled : (array) $enabled;
			$post_types = get_post_types( array( 'public' => true ), 'names' );

			echo '<div class="sr-checkbox-list">';
			foreach ( $post_types as $post_type ) {
				echo '<label>';
				echo '<input type="checkbox" name="enabled_post_types[]" value="' . esc_attr( $post_type ) . '"';
				checked( in_array( $post_type, $enabled, true ), true );
				echo ' />';
				echo '<span>' . esc_html( ucwords( $post_type ) ) . '</span>';
				echo '</label>';
			}
			echo '</div>';
		}

		/**
		 * Render stars style selector with visual previews.
		 */
		private function render_stars_style_field() {
			$current = get_option( 'stars_style', 'regular' );
			$styles  = array(
				'regular' => array(
					'label'   => esc_html__( 'Regular', 'stars-rating' ),
					'preview' => '☆☆☆☆☆',
				),
				'solid'   => array(
					'label'   => esc_html__( 'Solid', 'stars-rating' ),
					'preview' => '★★★★★',
				),
			);

			echo '<div class="sr-star-options">';
			foreach ( $styles as $value => $info ) {
				$id = 'stars_style_' . $value;
				echo '<div class="sr-star-option">';
				echo '<input type="radio" id="' . esc_attr( $id ) . '" name="stars_style" value="' . esc_attr( $value ) . '"';
				checked( $current, $value );
				echo ' />';
				echo '<label for="' . esc_attr( $id ) . '">';
				echo '<span class="sr-star-preview">' . esc_html( $info['preview'] ) . '</span>';
				echo '<span class="sr-star-label-text">' . esc_html( $info['label'] ) . '</span>';
				echo '</label>';
				echo '</div>';
			}
			echo '</div>';
		}
	}

	// Initialize the settings page.
	new Stars_Rating_Settings();
}
