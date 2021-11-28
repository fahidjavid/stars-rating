<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Stars_Rating_Metabox' ) ) :

	/**
	 * Class Stars_Rating_Metabox
	 *
	 * Plugin's settings class
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

		/**
		 * Status of the stars rating for the current post type
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		public static function status() {

			$enabled_posts = get_option( ' enabled_post_types', array( 'post', 'page' ) );

			if ( ! is_array( $enabled_posts ) ) {
				$enabled_posts = (array) $enabled_posts;
			}

			$status = in_array( get_post_type(), $enabled_posts ) ? true : false;

			return $status;
		}

		public function init_hooks() {

			add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 3 );

		}

		public function register_meta_box( $post_type ) {

			if ( $this->status() ) {

				add_meta_box(
					'stars-rating',
					esc_html__( 'Stars Rating', 'stars-rating' ),
					array( $this, 'render_meta_box' ),
					$post_type,
					'advanced',
					'high'
				);
			}
		}

		public function render_meta_box( $post ) {

			// Add nonce for security and authentication.
			wp_nonce_field( 'sr_nonce_action', 'sr_nonce' );

			$key       = 'sr-comments-rating';
			$current   = 1;
			$key_value = get_post_meta( $post->ID, $key, true );

			if ( '0' === $key_value || ! empty( $key_value ) ) {
				$current = $key_value;
			}
			?>
			<div id="sr-inner-container" class="sr-inner-container">
				<?php
				printf(
					'<br /><label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" class="selectit" %2$s/> %3$s</label>',
					$key,
					checked( 1, $current, false ),
					sprintf( esc_html__( 'Allow %s for comments on this page.', 'stars-rating' ), '<a href="https://wordpress.org/plugins/stars-rating/" target="_blank">Stars Rating</a>' )
				);
				?>
			</div><!-- /.sr-inner-container -->
			<?php
		}

		public function save_meta_box( $post_id ) {

			// Add nonce for security and authentication.
			$nonce_name = isset( $_POST['sr_nonce'] ) ? $_POST['sr_nonce'] : '';

			// Check if nonce is valid.
			if ( ! wp_verify_nonce( $nonce_name, 'sr_nonce_action' ) ) {
				return;
			}

			// Check if user has permissions to save data.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Check if not an autosave.
			if ( wp_is_post_autosave( $post_id ) ) {
				return;
			}

			// Check if not a revision.
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}


			// Missing capability
			if ( ! current_user_can( 'edit_' . $_POST['post_type'], $post_id ) ) {
				return;
			}

			$key = 'sr-comments-rating';

			// Checkbox successfully clicked
			if ( isset ( $_POST[ $key ] ) && 'on' === strtolower( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, 1 );
			} else {
				update_post_meta( $post_id, $key, 0 );
			}

		}
	}

endif;

/**
 * Main instance of Stars_Rating_Metabox.
 *
 * Returns the main instance of Stars_Rating_Metabox to prevent the need to use globals.
 *
 * @return Stars_Rating_Metabox
 * @since  1.0.0
 */
function Stars_Rating_Metabox() {
	return Stars_Rating_Metabox::instance();
}

// Get Stars_Rating_Metabox Running.
Stars_Rating_Metabox();