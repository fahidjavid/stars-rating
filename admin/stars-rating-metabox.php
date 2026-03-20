<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Stars_Rating_Metabox' ) ) :

	/**
	 * Class Stars_Rating_Metabox
	 *
	 * Sidebar metabox shown on the post edit screen for every post type that has
	 * either star ratings or likes/dislikes enabled. Lets editors override the
	 * global feature flags on a per-post basis.
	 *
	 * @since 1.0.0
	 */
	final class Stars_Rating_Metabox {

		/**
		 * Single instance of Class.
		 *
		 * @var Stars_Rating_Metabox
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
		 * Stars_Rating_Metabox constructor.
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->init_hooks();

			// Stars Rating plugin metabox loaded action hook
			do_action( 'Stars_Rating_Metabox_loaded' );

		}

		// ══════════════════════════════════════════════════════════════════════
		// Status helpers
		// ══════════════════════════════════════════════════════════════════════

		/**
		 * Whether star ratings are globally enabled for the given / current post type.
		 *
		 * @param string|null $post_type  Explicit post type, or null to use get_post_type().
		 * @return bool
		 * @since 1.0.0
		 */
		public static function status( string $post_type = null ): bool {
			$post_type     = $post_type ?? get_post_type();
			$enabled_posts = (array) get_option( 'enabled_post_types', array( 'post', 'page' ) );
			return in_array( $post_type, $enabled_posts, true );
		}

		/**
		 * Whether likes/dislikes are globally enabled for the given / current post type.
		 *
		 * @param string|null $post_type  Explicit post type, or null to use get_post_type().
		 * @return bool
		 */
		public static function likes_status( string $post_type = null ): bool {
			if ( 'enable' !== get_option( 'sr_likes_enabled', 'disable' ) ) {
				return false;
			}
			$post_type    = $post_type ?? get_post_type();
			$likes_types  = (array) get_option( 'sr_likes_post_types', array( 'post', 'page' ) );
			return in_array( $post_type, $likes_types, true );
		}

		// ══════════════════════════════════════════════════════════════════════
		// Hooks
		// ══════════════════════════════════════════════════════════════════════

		public function init_hooks() {
			add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
			add_action( 'save_post',      array( $this, 'save_meta_box' ), 10, 3 );
		}

		public function register_meta_box( string $post_type ) {

			// Show the metabox if at least one feature is active for this post type.
			if ( ! self::status( $post_type ) && ! self::likes_status( $post_type ) ) {
				return;
			}

			add_meta_box(
				'stars-rating',
				esc_html__( 'Stars Rating', 'stars-rating' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',      // sidebar — sits alongside Discussion, Publish, etc.
				'default'    // 'default' priority places it after core boxes like Discussion
			);
		}

		// ══════════════════════════════════════════════════════════════════════
		// Render
		// ══════════════════════════════════════════════════════════════════════

		public function render_meta_box( WP_Post $post ) {

			wp_nonce_field( 'sr_nonce_action', 'sr_nonce' );

			$show_stars = self::status( $post->post_type );
			$show_likes = self::likes_status( $post->post_type );

			// ── Stars Rating value ──────────────────────────────────────────
			$stars_key   = 'sr-comments-rating';
			$stars_value = get_post_meta( $post->ID, $stars_key, true );
			// Default to enabled (1) when meta has never been saved.
			$stars_on    = ( '0' !== $stars_value );

			// ── Likes/Dislikes value ────────────────────────────────────────
			$likes_key   = 'sr-likes-enabled';
			$likes_value = get_post_meta( $post->ID, $likes_key, true );
			// Default to enabled (1) when meta has never been saved.
			$likes_on    = ( '0' !== $likes_value );
			?>
			<div class="sr-metabox">

				<?php if ( $show_stars ) : ?>
				<label class="sr-metabox-row">
					<input
						type="checkbox"
						id="<?php echo esc_attr( $stars_key ); ?>"
						name="<?php echo esc_attr( $stars_key ); ?>"
						<?php checked( $stars_on ); ?>
					/>
					<span class="sr-metabox-label">
						<span class="sr-metabox-icon dashicons dashicons-star-filled"></span>
						<?php esc_html_e( 'Enable star ratings', 'stars-rating' ); ?>
					</span>
				</label>
				<?php endif; ?>

				<?php if ( $show_likes ) : ?>
				<label class="sr-metabox-row">
					<input
						type="checkbox"
						id="<?php echo esc_attr( $likes_key ); ?>"
						name="<?php echo esc_attr( $likes_key ); ?>"
						<?php checked( $likes_on ); ?>
					/>
					<span class="sr-metabox-label">
						<span class="sr-metabox-icon dashicons dashicons-thumbs-up"></span>
						<?php esc_html_e( 'Enable likes &amp; dislikes', 'stars-rating' ); ?>
					</span>
				</label>
				<?php endif; ?>

			</div><!-- /.sr-metabox -->
			<?php
		}

		// ══════════════════════════════════════════════════════════════════════
		// Save
		// ══════════════════════════════════════════════════════════════════════

		public function save_meta_box( int $post_id ) {

			// Nonce check.
			$nonce = isset( $_POST['sr_nonce'] ) ? $_POST['sr_nonce'] : '';
			if ( ! wp_verify_nonce( $nonce, 'sr_nonce_action' ) ) {
				return;
			}

			// Capability checks.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			if ( wp_is_post_autosave( $post_id ) ) {
				return;
			}
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}
			if ( isset( $_POST['post_type'] ) && ! current_user_can( 'edit_' . sanitize_key( $_POST['post_type'] ), $post_id ) ) {
				return;
			}

			// ── Stars Rating ────────────────────────────────────────────────
			$stars_key = 'sr-comments-rating';
			update_post_meta(
				$post_id,
				$stars_key,
				isset( $_POST[ $stars_key ] ) ? 1 : 0
			);

			// ── Likes / Dislikes ────────────────────────────────────────────
			$likes_key = 'sr-likes-enabled';
			update_post_meta(
				$post_id,
				$likes_key,
				isset( $_POST[ $likes_key ] ) ? 1 : 0
			);
		}
	}

endif;

/**
 * Main instance of Stars_Rating_Metabox.
 *
 * @return Stars_Rating_Metabox
 * @since  1.0.0
 */
function Stars_Rating_Metabox() {
	return Stars_Rating_Metabox::instance();
}

// Get Stars_Rating_Metabox Running.
Stars_Rating_Metabox();
