<?php
/**
 * Handles the post likes / dislikes feature.
 *
 * - Appends like & dislike buttons after post content on enabled post types.
 * - Processes votes via WP AJAX (logged-in + guest paths).
 * - Stores likes / dislikes as post meta (_sr_likes / _sr_dislikes).
 * - Deduplicates votes per user (user meta) or per device (cookie).
 *
 * @since 4.0.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stars_Rating_Likes' ) ) :

	class Stars_Rating_Likes {

		/** @var Stars_Rating_Likes */
		protected static $_instance;

		public static function instance(): self {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function __construct() {
			$this->init_hooks();
		}

		// ══════════════════════════════════════════════════════════════════════
		// Helpers
		// ══════════════════════════════════════════════════════════════════════

		/**
		 * Whether the likes/dislikes feature is enabled in settings.
		 */
		public static function is_enabled(): bool {
			return 'enable' === get_option( 'sr_likes_enabled', 'disable' );
		}

		/**
		 * Whether the current post type has likes enabled.
		 * Checks both the global feature flag and the enabled post types list.
		 */
		public static function is_eligible(): bool {
			if ( ! self::is_enabled() ) {
				return false;
			}
			$enabled = (array) get_option( 'sr_likes_post_types', array( 'post', 'page' ) );
			if ( ! in_array( get_post_type(), $enabled, true ) ) {
				return false;
			}
			// Respect per-post override (default: enabled when meta is absent).
			$post_meta = get_post_meta( get_the_ID(), 'sr-likes-enabled', true );
			return '0' !== $post_meta;
		}

		/**
		 * Return the current visitor's vote for a given post: 'like', 'dislike', or ''.
		 */
		public static function get_user_vote( int $post_id ): string {
			if ( is_user_logged_in() ) {
				$vote = get_user_meta( get_current_user_id(), 'sr_vote_' . $post_id, true );
			} else {
				$cookie = 'sr_vote_' . $post_id;
				$vote   = isset( $_COOKIE[ $cookie ] ) ? sanitize_key( $_COOKIE[ $cookie ] ) : '';
			}
			return in_array( $vote, array( 'like', 'dislike' ), true ) ? $vote : '';
		}

		// ══════════════════════════════════════════════════════════════════════
		// Hooks
		// ══════════════════════════════════════════════════════════════════════

		public function init_hooks(): void {
			add_filter( 'the_content',              array( $this, 'append_buttons' ) );
			add_action( 'wp_ajax_sr_vote',          array( $this, 'handle_vote' ) );
			add_action( 'wp_ajax_nopriv_sr_vote',   array( $this, 'handle_vote_guest' ) );
		}

		// ══════════════════════════════════════════════════════════════════════
		// Frontend
		// ══════════════════════════════════════════════════════════════════════

		/**
		 * Append like / dislike buttons after post content.
		 */
		public function append_buttons( string $content ): string {

			if ( ! is_singular() || ! is_main_query() ) {
				return $content;
			}

			if ( ! self::is_eligible() ) {
				return $content;
			}

			$post_id    = get_the_ID();
			$likes      = absint( get_post_meta( $post_id, '_sr_likes',    true ) );
			$dislikes   = absint( get_post_meta( $post_id, '_sr_dislikes', true ) );
			$user_vote  = self::get_user_vote( $post_id );
			$show_count = 'yes' === get_option( 'sr_likes_show_count', 'yes' );
			$voters     = get_option( 'sr_likes_voters', 'everyone' );
			$nonce      = wp_create_nonce( 'sr_vote_' . $post_id );

			$liked_class    = ( 'like'    === $user_vote ) ? ' sr-voted' : '';
			$disliked_class = ( 'dislike' === $user_vote ) ? ' sr-voted' : '';

			// If guest voting is disabled and user is not logged in, mark as login-required.
			$login_required = ( 'logged_in' === $voters && ! is_user_logged_in() ) ? 'true' : 'false';

			ob_start();
			?>
			<div class="sr-likes-wrap"
				data-post-id="<?php echo absint( $post_id ); ?>"
				data-nonce="<?php echo esc_attr( $nonce ); ?>"
				data-login-required="<?php echo esc_attr( $login_required ); ?>">

				<span class="sr-likes-label"><?php echo esc_html( get_option( 'sr_str_likes_label', __( 'Was this helpful?', 'stars-rating' ) ) ); ?></span>

				<div class="sr-likes-buttons">

					<button type="button"
						class="sr-vote-btn sr-like-btn<?php echo esc_attr( $liked_class ); ?>"
						data-vote="like"
						aria-pressed="<?php echo ( 'like' === $user_vote ) ? 'true' : 'false'; ?>"
						aria-label="<?php esc_attr_e( 'Like this post', 'stars-rating' ); ?>">
						<svg class="sr-icon sr-icon-up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>
						<span class="sr-vote-label"><?php echo esc_html( get_option( 'sr_str_likes_yes', __( 'Yes', 'stars-rating' ) ) ); ?></span>
						<?php if ( $show_count ) : ?>
							<span class="sr-vote-count"><?php echo absint( $likes ); ?></span>
						<?php endif; ?>
					</button>

					<button type="button"
						class="sr-vote-btn sr-dislike-btn<?php echo esc_attr( $disliked_class ); ?>"
						data-vote="dislike"
						aria-pressed="<?php echo ( 'dislike' === $user_vote ) ? 'true' : 'false'; ?>"
						aria-label="<?php esc_attr_e( 'Dislike this post', 'stars-rating' ); ?>">
						<svg class="sr-icon sr-icon-dn" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L10.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"/></svg>
						<span class="sr-vote-label"><?php echo esc_html( get_option( 'sr_str_likes_no', __( 'No', 'stars-rating' ) ) ); ?></span>
						<?php if ( $show_count ) : ?>
							<span class="sr-vote-count"><?php echo absint( $dislikes ); ?></span>
						<?php endif; ?>
					</button>

				</div><!-- .sr-likes-buttons -->

				<span class="sr-likes-feedback" aria-live="polite"></span>

			</div><!-- .sr-likes-wrap -->
			<?php

			return $content . ob_get_clean();
		}

		// ══════════════════════════════════════════════════════════════════════
		// AJAX
		// ══════════════════════════════════════════════════════════════════════

		/** Handle vote from a logged-in user. */
		public function handle_vote(): void {
			$this->process_vote( true );
		}

		/** Handle vote from a guest — blocked if "logged in only" option is set. */
		public function handle_vote_guest(): void {
			if ( 'logged_in' === get_option( 'sr_likes_voters', 'everyone' ) ) {
				wp_send_json_error(
					array( 'message' => __( 'You must be logged in to vote.', 'stars-rating' ) ),
					403
				);
			}
			$this->process_vote( false );
		}

		/**
		 * Core vote processing shared by both AJAX handlers.
		 *
		 * @param bool $logged_in Whether the current visitor is logged in.
		 */
		private function process_vote( bool $logged_in ): void {

			$post_id   = absint( $_POST['post_id'] ?? 0 );
			$vote_type = sanitize_key( $_POST['vote']    ?? '' );
			$nonce     = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );

			// Basic input validation.
			if ( ! $post_id || ! in_array( $vote_type, array( 'like', 'dislike' ), true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'stars-rating' ) ), 400 );
			}

			// Nonce check.
			if ( ! wp_verify_nonce( $nonce, 'sr_vote_' . $post_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'stars-rating' ) ), 403 );
			}

			// Feature must be enabled.
			if ( ! self::is_enabled() ) {
				wp_send_json_error( array( 'message' => __( 'Feature not enabled.', 'stars-rating' ) ), 403 );
			}

			// Post must exist and belong to an enabled post type.
			$post = get_post( $post_id );
			if ( ! $post ) {
				wp_send_json_error( array( 'message' => __( 'Post not found.', 'stars-rating' ) ), 404 );
			}
			$enabled = (array) get_option( 'sr_likes_post_types', array( 'post', 'page' ) );
			if ( ! in_array( $post->post_type, $enabled, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Feature not enabled for this post type.', 'stars-rating' ) ), 403 );
			}

			// Respect per-post override.
			$likes_meta = get_post_meta( $post_id, 'sr-likes-enabled', true );
			if ( '0' === $likes_meta ) {
				wp_send_json_error( array( 'message' => __( 'Likes & dislikes are disabled for this post.', 'stars-rating' ) ), 403 );
			}

			// Retrieve existing vote.
			$existing_vote = '';
			if ( $logged_in ) {
				$stored        = get_user_meta( get_current_user_id(), 'sr_vote_' . $post_id, true );
				$existing_vote = in_array( $stored, array( 'like', 'dislike' ), true ) ? $stored : '';
			} else {
				$cookie_key    = 'sr_vote_' . $post_id;
				$stored        = isset( $_COOKIE[ $cookie_key ] ) ? sanitize_key( $_COOKIE[ $cookie_key ] ) : '';
				$existing_vote = in_array( $stored, array( 'like', 'dislike' ), true ) ? $stored : '';
			}

			// Compute updated counts.
			$likes    = absint( get_post_meta( $post_id, '_sr_likes',    true ) );
			$dislikes = absint( get_post_meta( $post_id, '_sr_dislikes', true ) );

			if ( $existing_vote === $vote_type ) {
				// Same button clicked again → toggle off (remove vote).
				if ( 'like' === $vote_type ) {
					$likes = max( 0, $likes - 1 );
				} else {
					$dislikes = max( 0, $dislikes - 1 );
				}
				$new_vote = '';
			} else {
				// New vote or switching from the opposite button.
				if ( 'like' === $vote_type ) {
					$likes++;
					if ( 'dislike' === $existing_vote ) {
						$dislikes = max( 0, $dislikes - 1 );
					}
				} else {
					$dislikes++;
					if ( 'like' === $existing_vote ) {
						$likes = max( 0, $likes - 1 );
					}
				}
				$new_vote = $vote_type;
			}

			// Persist counts.
			update_post_meta( $post_id, '_sr_likes',    $likes    );
			update_post_meta( $post_id, '_sr_dislikes', $dislikes );

			// Persist user preference (user meta or cookie).
			if ( $logged_in ) {
				$user_id = get_current_user_id();
				if ( '' === $new_vote ) {
					delete_user_meta( $user_id, 'sr_vote_' . $post_id );
				} else {
					update_user_meta( $user_id, 'sr_vote_' . $post_id, $new_vote );
				}
			} else {
				$cookie_key = 'sr_vote_' . $post_id;
				if ( '' === $new_vote ) {
					setcookie( $cookie_key, '', array(
						'expires'  => time() - 3600,
						'path'     => '/',
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					) );
				} else {
					setcookie( $cookie_key, $new_vote, array(
						'expires'  => time() + YEAR_IN_SECONDS,
						'path'     => '/',
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					) );
				}
			}

			wp_send_json_success( array(
				'likes'     => $likes,
				'dislikes'  => $dislikes,
				'user_vote' => $new_vote,
			) );
		}
	}

	/**
	 * Returns the singleton instance.
	 *
	 * @since 4.0.8
	 * @return Stars_Rating_Likes
	 */
	function Stars_Rating_Likes(): Stars_Rating_Likes {
		return Stars_Rating_Likes::instance();
	}

	Stars_Rating_Likes();

endif;
