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
							<span><?php printf( 'Built with <span class="sr-heart">♥</span> by <a href="https://fahidjavid.com" target="_blank" rel="noopener noreferrer">Fahid Javid</a>' ); ?></span>
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

						<!-- Likes & Dislikes Card -->
						<div class="sr-card">
							<div class="sr-card-header">
								<span class="dashicons dashicons-thumbs-up"></span>
								<h2><?php esc_html_e( 'Likes &amp; Dislikes', 'stars-rating' ); ?></h2>
							</div>
							<div class="sr-card-body">

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Enable Likes &amp; Dislikes', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Let visitors like or dislike posts on enabled post types.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'sr_likes_enabled', 'disable', array(
											'enable'  => esc_html__( 'Enable',  'stars-rating' ),
											'disable' => esc_html__( 'Disable', 'stars-rating' ),
										) ); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Post Types', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Select which post types show like/dislike buttons.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_likes_post_types_checkboxes(); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Who Can Vote', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Allow everyone to vote, or restrict to logged-in users only.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'sr_likes_voters', 'everyone', array(
											'everyone'  => esc_html__( 'Everyone',  'stars-rating' ),
											'logged_in' => esc_html__( 'Logged in', 'stars-rating' ),
										) ); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Show Count', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Display the number of likes and dislikes next to each button.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'sr_likes_show_count', 'yes', array(
											'yes' => esc_html__( 'Yes', 'stars-rating' ),
											'no'  => esc_html__( 'No',  'stars-rating' ),
										) ); ?>
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

						<!-- Review Photos Card -->
						<div class="sr-card">
							<div class="sr-card-header">
								<span class="dashicons dashicons-camera-alt"></span>
								<h2><?php esc_html_e( 'Review Photos', 'stars-rating' ); ?></h2>
							</div>
							<div class="sr-card-body">

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Enable Review Photos', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Allow reviewers to attach images to their comments.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'sr_photos_enabled', 'disable', array(
											'enable'  => esc_html__( 'Enable',  'stars-rating' ),
											'disable' => esc_html__( 'Disable', 'stars-rating' ),
										) ); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Post Types', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Select which post types allow photo uploads on reviews.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_photos_post_types_checkboxes(); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Who Can Upload', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Allow everyone to upload photos, or restrict to logged-in users only.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<?php $this->render_radio_field( 'sr_photos_voters', 'everyone', array(
											'everyone'  => esc_html__( 'Everyone',   'stars-rating' ),
											'logged_in' => esc_html__( 'Logged in',  'stars-rating' ),
										) ); ?>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Max Photos per Review', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Maximum number of images a reviewer can attach (1–10).', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<input
											type="number"
											name="sr_photos_max_count"
											class="sr-input sr-input-number"
											value="<?php echo esc_attr( get_option( 'sr_photos_max_count', 3 ) ); ?>"
											min="1" max="10" step="1"
										/>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Max File Size (MB)', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Maximum size in megabytes per uploaded image.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<input
											type="number"
											name="sr_photos_max_size_mb"
											class="sr-input sr-input-number"
											value="<?php echo esc_attr( get_option( 'sr_photos_max_size_mb', 2 ) ); ?>"
											min="1" max="20" step="1"
										/>
									</div>
								</div>

								<div class="sr-field">
									<div class="sr-field-label">
										<strong><?php esc_html_e( 'Max Image Dimension (px)', 'stars-rating' ); ?></strong>
										<p class="sr-desc"><?php esc_html_e( 'Images are resized to fit within this pixel dimension on upload. Saves disk space.', 'stars-rating' ); ?></p>
									</div>
									<div class="sr-field-input">
										<input
											type="number"
											name="sr_photos_thumb_size"
											class="sr-input sr-input-number"
											value="<?php echo esc_attr( get_option( 'sr_photos_thumb_size', 800 ) ); ?>"
											min="200" max="3000" step="50"
										/>
									</div>
								</div>

							</div>
						</div>

					</div><!-- .sr-grid -->

					<!-- Labels & Messages Card -->
					<div class="sr-card sr-card-full">
						<div class="sr-card-header">
							<span class="dashicons dashicons-editor-quote"></span>
							<h2><?php esc_html_e( 'Labels &amp; Messages', 'stars-rating' ); ?></h2>
						</div>
						<div class="sr-card-body">

							<p class="sr-section-title"><?php esc_html_e( 'Star Ratings', 'stars-rating' ); ?></p>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Require Rating Error', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Shown when a user submits a comment without selecting a rating.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_require_rating" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_require_rating', __( 'Please select a star rating before submitting your review.', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'No Reviews Yet', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Shown above the comment form when no reviews have been posted yet.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_first_review" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_first_review', __( 'Be the first to write a review', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Average Rating Text', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Displayed as: "4.5 [Prefix] 12 [Suffix]". Adjust wording to match your language.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input sr-field-inline">
									<input type="text" name="sr_str_avg_based_on" class="sr-input sr-input-short"
										value="<?php echo esc_attr( get_option( 'sr_str_avg_based_on', __( 'based on', 'stars-rating' ) ) ); ?>"
										placeholder="<?php esc_attr_e( 'based on', 'stars-rating' ); ?>" />
									<input type="text" name="sr_str_avg_reviews" class="sr-input sr-input-short"
										value="<?php echo esc_attr( get_option( 'sr_str_avg_reviews', __( 'reviews', 'stars-rating' ) ) ); ?>"
										placeholder="<?php esc_attr_e( 'reviews', 'stars-rating' ); ?>" />
								</div>
							</div>

							<p class="sr-section-title"><?php esc_html_e( 'Negative Rating Alert', 'stars-rating' ); ?></p>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Alert Message', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Popup text shown when a reviewer selects a low star rating.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<textarea name="sr_str_alert_message" class="sr-input sr-textarea" rows="3"><?php echo esc_textarea( get_option( 'sr_str_alert_message', __( "We\xe2\x80\x99re sorry you\xe2\x80\x99ve had a bad experience. Before you post your review, feel free to contact us, so we can help resolve your issue.", 'stars-rating' ) ) ); ?></textarea>
								</div>
							</div>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Post Review Button', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Label for the "continue anyway" link in the alert popup.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_alert_post_review" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_alert_post_review', __( 'Post Review', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Contact Us Button', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Label for the contact link in the alert popup.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_alert_contact_us" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_alert_contact_us', __( 'Contact Us', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

							<p class="sr-section-title"><?php esc_html_e( 'Likes &amp; Dislikes', 'stars-rating' ); ?></p>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Prompt Label', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Question displayed next to the like/dislike buttons.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_likes_label" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_likes_label', __( 'Was this helpful?', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Like Button', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Label text on the thumbs-up (Yes) button.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_likes_yes" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_likes_yes', __( 'Yes', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Dislike Button', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Label text on the thumbs-down (No) button.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_likes_no" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_likes_no', __( 'No', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Login Required Message', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Shown when a guest tries to vote but only logged-in users are allowed.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_likes_must_login" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_likes_must_login', __( 'You must be logged in to vote.', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

							<div class="sr-field">
								<div class="sr-field-label">
									<strong><?php esc_html_e( 'Thank You Message', 'stars-rating' ); ?></strong>
									<p class="sr-desc"><?php esc_html_e( 'Brief confirmation shown after a successful vote.', 'stars-rating' ); ?></p>
								</div>
								<div class="sr-field-input">
									<input type="text" name="sr_str_likes_thanks" class="sr-input"
										value="<?php echo esc_attr( get_option( 'sr_str_likes_thanks', __( 'Thanks for the feedback!', 'stars-rating' ) ) ); ?>" />
								</div>
							</div>

						</div>
					</div><!-- .sr-card-full -->

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
			register_setting( 'stars_rating_settings', 'sr_likes_enabled', array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'disable',
			) );
			register_setting( 'stars_rating_settings', 'sr_likes_voters', array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'everyone',
			) );
			register_setting( 'stars_rating_settings', 'sr_likes_show_count', array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'yes',
			) );
			register_setting( 'stars_rating_settings', 'sr_likes_post_types', array(
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
				'default'           => array( 'post', 'page' ),
			) );

			// Labels & Messages.
			$str_fields = array(
				'sr_str_require_rating',
				'sr_str_first_review',
				'sr_str_avg_based_on',
				'sr_str_avg_reviews',
				'sr_str_alert_message',
				'sr_str_alert_post_review',
				'sr_str_alert_contact_us',
				'sr_str_likes_label',
				'sr_str_likes_yes',
				'sr_str_likes_no',
				'sr_str_likes_must_login',
				'sr_str_likes_thanks',
			);
			foreach ( $str_fields as $key ) {
				register_setting( 'stars_rating_settings', $key, array(
					'sanitize_callback' => 'sanitize_textarea_field',
				) );
			}

			// Review Photos.
			register_setting( 'stars_rating_settings', 'sr_photos_enabled', array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'disable',
			) );
			register_setting( 'stars_rating_settings', 'sr_photos_post_types', array(
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
				'default'           => array( 'post', 'page' ),
			) );
			register_setting( 'stars_rating_settings', 'sr_photos_voters', array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'everyone',
			) );
			register_setting( 'stars_rating_settings', 'sr_photos_max_count', array(
				'sanitize_callback' => 'absint',
				'default'           => 3,
			) );
			register_setting( 'stars_rating_settings', 'sr_photos_max_size_mb', array(
				'sanitize_callback' => 'absint',
				'default'           => 2,
			) );
			register_setting( 'stars_rating_settings', 'sr_photos_thumb_size', array(
				'sanitize_callback' => 'absint',
				'default'           => 800,
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
		 * Render post type checkboxes for the likes/dislikes feature.
		 */
		private function render_likes_post_types_checkboxes() {
			$enabled    = get_option( 'sr_likes_post_types', array( 'post', 'page' ) );
			$enabled    = is_array( $enabled ) ? $enabled : (array) $enabled;
			$post_types = get_post_types( array( 'public' => true ), 'names' );

			echo '<div class="sr-checkbox-list">';
			foreach ( $post_types as $post_type ) {
				echo '<label>';
				echo '<input type="checkbox" name="sr_likes_post_types[]" value="' . esc_attr( $post_type ) . '"';
				checked( in_array( $post_type, $enabled, true ), true );
				echo ' />';
				echo '<span>' . esc_html( ucwords( $post_type ) ) . '</span>';
				echo '</label>';
			}
			echo '</div>';
		}

		/**
		 * Render post type checkboxes for the review photos feature.
		 */
		private function render_photos_post_types_checkboxes() {
			$enabled    = get_option( 'sr_photos_post_types', array( 'post', 'page' ) );
			$enabled    = is_array( $enabled ) ? $enabled : (array) $enabled;
			$post_types = get_post_types( array( 'public' => true ), 'names' );

			echo '<div class="sr-checkbox-list">';
			foreach ( $post_types as $post_type ) {
				echo '<label>';
				echo '<input type="checkbox" name="sr_photos_post_types[]" value="' . esc_attr( $post_type ) . '"';
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
