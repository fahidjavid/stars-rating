<?php
/**
 * Review photo uploads for the Stars Rating plugin.
 *
 * Files are stored in wp-content/uploads/sr-reviews/YYYY/MM/ and are NEVER
 * registered in the WordPress Media Library, keeping it completely clutter-free.
 * File paths (relative to the WP uploads base) are tracked via comment meta.
 *
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stars_Rating_Review_Photos' ) ) {

	class Stars_Rating_Review_Photos {

		/** @var self */
		protected static $_instance;

		/** Subdirectory name inside wp-content/uploads/ */
		const BASE_DIR = 'sr-reviews';

		/** Accepted MIME types */
		const ALLOWED_MIME = array(
			'image/jpeg',
			'image/png',
			'image/webp',
			'image/gif',
		);

		public function __construct() {
			$this->init_hooks();
		}

		public static function instance(): self {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		private function init_hooks(): void {

			if ( ! is_admin() ) {
				// Render the file input inside the comment form.
				add_action( 'comment_form_top', array( $this, 'add_file_input' ) );
				// Lightbox shell + JS injected once at wp_footer.
				add_action( 'wp_footer', array( $this, 'render_lightbox_shell' ) );
			}

			// Core comment lifecycle — registered on every public request.
			add_filter( 'preprocess_comment', array( $this, 'validate_photos' ) );
			add_action( 'comment_post',       array( $this, 'save_photos' ) );
			add_filter( 'comment_text',       array( $this, 'display_photos' ), 20, 2 );
			add_action( 'delete_comment',     array( $this, 'delete_photos' ) );

			// Admin: metabox on comment edit screen + per-photo delete via AJAX.
			if ( is_admin() ) {
				add_action( 'add_meta_boxes_comment',         array( $this, 'add_photos_meta_box' ) );
				add_action( 'wp_ajax_sr_delete_review_photo', array( $this, 'ajax_delete_single_photo' ) );
			}
		}

		// ══════════════════════════════════════════════════════════════════
		// Eligibility helpers
		// ══════════════════════════════════════════════════════════════════

		public static function is_enabled(): bool {
			return 'enable' === get_option( 'sr_photos_enabled', 'disable' );
		}

		/**
		 * Whether photo uploads are allowed for the given (or current) post type.
		 *
		 * @param string|null $post_type Explicit post type string, or null to use get_post_type().
		 */
		public static function is_eligible( ?string $post_type = null ): bool {
			if ( ! self::is_enabled() ) {
				return false;
			}
			$types = (array) get_option( 'sr_photos_post_types', array( 'post', 'page' ) );
			return in_array( $post_type ?? get_post_type(), $types, true );
		}

		// ══════════════════════════════════════════════════════════════════
		// Upload directory helpers
		// ══════════════════════════════════════════════════════════════════

		/**
		 * Returns the upload path and public URL for the current YYYY/MM directory.
		 *
		 * @return array{ path: string, url: string, year: string, month: string }
		 */
		private function get_upload_dir(): array {
			$uploads = wp_upload_dir();
			$year    = gmdate( 'Y' );
			$month   = gmdate( 'm' );
			$subdir  = self::BASE_DIR . "/{$year}/{$month}";

			return array(
				'path'  => trailingslashit( $uploads['basedir'] ) . $subdir,
				'url'   => trailingslashit( $uploads['baseurl'] ) . $subdir,
				'year'  => $year,
				'month' => $month,
			);
		}

		/**
		 * Creates the directory (recursively) and drops protective index.php / .htaccess
		 * at the sr-reviews/ root on first use.
		 */
		private function ensure_upload_dir( string $path ): bool {
			if ( is_dir( $path ) ) {
				return true;
			}

			if ( ! wp_mkdir_p( $path ) ) {
				return false;
			}

			$base = trailingslashit( wp_upload_dir()['basedir'] ) . self::BASE_DIR;

			if ( ! file_exists( $base . '/index.php' ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $base . '/index.php', "<?php\n// Silence is golden." );
			}

			if ( ! file_exists( $base . '/.htaccess' ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $base . '/.htaccess', "Options -Indexes\n" );
			}

			return true;
		}

		// ══════════════════════════════════════════════════════════════════
		// Comment form — file input + enctype injection
		// ══════════════════════════════════════════════════════════════════

		public function add_file_input(): void {
			if ( ! self::is_eligible() ) {
				return;
			}

			$voters = get_option( 'sr_photos_voters', 'everyone' );
			if ( 'logged_in' === $voters && ! is_user_logged_in() ) {
				return;
			}

			$max_count = absint( get_option( 'sr_photos_max_count', 3 ) );
			$max_mb    = absint( get_option( 'sr_photos_max_size_mb', 2 ) );
			?>
			<div class="sr-photo-upload-wrap">
				<label class="sr-photo-label">
					<span class="dashicons dashicons-camera-alt sr-photo-icon"></span>
					<span class="sr-photo-label-text">
						<?php
						printf(
							/* translators: 1: max number of photos, 2: max file size in MB */
							esc_html__( 'Add photos (up to %1$d, %2$dMB each)', 'stars-rating' ),
							$max_count,
							$max_mb
						);
						?>
					</span>
					<input
						type="file"
						name="sr_review_images[]"
						id="sr_review_images"
						accept="image/jpeg,image/png,image/webp,image/gif"
						<?php echo $max_count > 1 ? 'multiple' : ''; ?>
						class="sr-photo-input"
						data-max="<?php echo esc_attr( $max_count ); ?>"
					/>
				</label>
				<div id="sr-photo-preview" class="sr-photo-preview" aria-live="polite"></div>
			</div>
			<script>
			(function(){
				// Add multipart enctype so $_FILES is populated on submit.
				var form=document.getElementById('commentform');
				if(form){form.setAttribute('enctype','multipart/form-data');}

				// Live photo preview before upload.
				var input=document.getElementById('sr_review_images');
				var preview=document.getElementById('sr-photo-preview');
				if(!input||!preview) return;
				input.addEventListener('change',function(){
					preview.innerHTML='';
					var files=this.files;
					if(!files||!files.length) return;
					var max=parseInt(this.dataset.max||'3',10);
					for(var i=0;i<Math.min(files.length,max);i++){
						if(!files[i].type.match(/^image\//)) continue;
						(function(file){
							var r=new FileReader();
							r.onload=function(e){
								var img=document.createElement('img');
								img.src=e.target.result;
								img.className='sr-photo-thumb';
								img.alt='';
								preview.appendChild(img);
							};
							r.readAsDataURL(file);
						})(files[i]);
					}
				});
			})();
			</script>
			<?php
		}

		// ══════════════════════════════════════════════════════════════════
		// Validation (preprocess_comment filter)
		// ══════════════════════════════════════════════════════════════════

		public function validate_photos( array $commentdata ): array {
			if ( ! self::is_enabled() ) {
				return $commentdata;
			}

			// No files submitted — nothing to validate.
			if ( empty( $_FILES['sr_review_images']['name'][0] ) ) {
				return $commentdata;
			}

			// Bail silently if the post type is not eligible.
			$post_id = absint( $commentdata['comment_post_ID'] ?? 0 );
			if ( $post_id && ! self::is_eligible( get_post_type( $post_id ) ) ) {
				return $commentdata;
			}

			// Bail silently if guests are not allowed.
			$voters = get_option( 'sr_photos_voters', 'everyone' );
			if ( 'logged_in' === $voters && ! is_user_logged_in() ) {
				return $commentdata;
			}

			$max_count = absint( get_option( 'sr_photos_max_count', 3 ) );
			$max_bytes = absint( get_option( 'sr_photos_max_size_mb', 2 ) ) * MB_IN_BYTES;
			$files     = $_FILES['sr_review_images'];
			$count     = is_array( $files['name'] ) ? count( $files['name'] ) : 1;

			if ( $count > $max_count ) {
				wp_die(
					sprintf(
						/* translators: %d: maximum allowed photos */
						esc_html__( 'Error: You may upload a maximum of %d photo(s) per review. Please go back and try again.', 'stars-rating' ),
						$max_count
					)
				);
			}

			for ( $i = 0; $i < $count; $i++ ) {
				$name  = is_array( $files['name'] )     ? $files['name'][ $i ]     : $files['name'];
				$tmp   = is_array( $files['tmp_name'] ) ? $files['tmp_name'][ $i ] : $files['tmp_name'];
				$size  = is_array( $files['size'] )     ? $files['size'][ $i ]     : $files['size'];
				$error = is_array( $files['error'] )    ? $files['error'][ $i ]    : $files['error'];

				if ( UPLOAD_ERR_NO_FILE === $error ) {
					continue;
				}

				if ( UPLOAD_ERR_OK !== $error ) {
					wp_die( esc_html__( 'Error: A problem occurred while uploading one of your photos. Please go back and try again.', 'stars-rating' ) );
				}

				if ( $size > $max_bytes ) {
					wp_die(
						sprintf(
							/* translators: 1: filename, 2: max size in MB */
							esc_html__( 'Error: "%1$s" exceeds the maximum allowed file size of %2$d MB.', 'stars-rating' ),
							esc_html( wp_basename( $name ) ),
							absint( get_option( 'sr_photos_max_size_mb', 2 ) )
						)
					);
				}

				// Validate MIME type via both extension and finfo (more secure than extension alone).
				$check = wp_check_filetype_and_ext( $tmp, $name );
				if ( ! in_array( $check['type'], self::ALLOWED_MIME, true ) ) {
					wp_die(
						sprintf(
							/* translators: %s: filename */
							esc_html__( 'Error: "%s" is not an allowed image type. Please upload JPEG, PNG, WebP, or GIF files only.', 'stars-rating' ),
							esc_html( wp_basename( $name ) )
						)
					);
				}
			}

			return $commentdata;
		}

		// ══════════════════════════════════════════════════════════════════
		// Save photos (comment_post action)
		// ══════════════════════════════════════════════════════════════════

		public function save_photos( int $comment_id ): void {
			if ( ! self::is_enabled() ) {
				return;
			}

			if ( empty( $_FILES['sr_review_images']['name'][0] ) ) {
				return;
			}

			$comment = get_comment( $comment_id );
			if ( ! $comment || ! empty( $comment->comment_parent ) ) {
				return; // No photos on replies.
			}

			if ( ! self::is_eligible( get_post_type( (int) $comment->comment_post_ID ) ) ) {
				return;
			}

			$upload = $this->get_upload_dir();
			if ( ! $this->ensure_upload_dir( $upload['path'] ) ) {
				return;
			}

			$max_count   = absint( get_option( 'sr_photos_max_count', 3 ) );
			$max_dim     = absint( get_option( 'sr_photos_thumb_size', 800 ) );
			$files       = $_FILES['sr_review_images'];
			$total       = is_array( $files['name'] ) ? count( $files['name'] ) : 1;
			$total       = min( $total, $max_count );
			$saved_paths = array();

			for ( $i = 0; $i < $total; $i++ ) {
				$tmp   = is_array( $files['tmp_name'] ) ? $files['tmp_name'][ $i ] : $files['tmp_name'];
				$name  = is_array( $files['name'] )     ? $files['name'][ $i ]     : $files['name'];
				$error = is_array( $files['error'] )    ? $files['error'][ $i ]    : $files['error'];

				if ( UPLOAD_ERR_OK !== $error || empty( $tmp ) || ! is_uploaded_file( $tmp ) ) {
					continue;
				}

				$check = wp_check_filetype_and_ext( $tmp, $name );
				if ( empty( $check['ext'] ) || ! in_array( $check['type'], self::ALLOWED_MIME, true ) ) {
					continue;
				}

				// Unique filename: {comment_id}_{index}_{uniqid}.{ext}
				$filename = sprintf( '%d_%d_%s.%s', $comment_id, $i, uniqid( '', true ), $check['ext'] );
				$dest     = trailingslashit( $upload['path'] ) . $filename;
				$rel_path = self::BASE_DIR . '/' . $upload['year'] . '/' . $upload['month'] . '/' . $filename;

				// Resize with WP_Image_Editor; fall back to raw move if unsupported.
				$editor = wp_get_image_editor( $tmp );
				if ( ! is_wp_error( $editor ) ) {
					$editor->resize( $max_dim, $max_dim, false );
					$result = $editor->save( $dest );
					if ( ! is_wp_error( $result ) ) {
						$saved_paths[] = $rel_path;
					}
				} elseif ( move_uploaded_file( $tmp, $dest ) ) {
					$saved_paths[] = $rel_path;
				}
			}

			if ( ! empty( $saved_paths ) ) {
				update_comment_meta( $comment_id, '_sr_review_images', $saved_paths );
			}
		}

		// ══════════════════════════════════════════════════════════════════
		// Display photos in comment text
		// ══════════════════════════════════════════════════════════════════

		public function display_photos( string $comment_text, $comment ): string {
			if ( ! self::is_enabled() || is_admin() ) {
				return $comment_text;
			}

			$images = get_comment_meta( $comment->comment_ID, '_sr_review_images', true );
			if ( empty( $images ) || ! is_array( $images ) ) {
				return $comment_text;
			}

			$base_url = trailingslashit( wp_upload_dir()['baseurl'] );
			$gallery  = 'c' . absint( $comment->comment_ID );
			$html     = '<div class="sr-review-photos">';
			$idx      = 0;

			foreach ( $images as $rel ) {
				$url   = esc_url( $base_url . $rel );
				$html .= '<button type="button" class="sr-lb-trigger"'
				       . ' data-src="' . esc_attr( $url ) . '"'
				       . ' data-sr-gallery="' . esc_attr( $gallery ) . '"'
				       . ' data-index="' . $idx . '"'
				       . ' aria-label="' . esc_attr__( 'View photo', 'stars-rating' ) . '">';
				$html .= '<img src="' . $url . '" alt="" loading="lazy" />';
				$html .= '</button>';
				$idx++;
			}

			$html .= '</div>';

			return $comment_text . $html;
		}

		// ══════════════════════════════════════════════════════════════════
		// Cleanup when a comment is deleted
		// ══════════════════════════════════════════════════════════════════

		public function delete_photos( int $comment_id ): void {
			$images = get_comment_meta( $comment_id, '_sr_review_images', true );
			if ( empty( $images ) || ! is_array( $images ) ) {
				return;
			}

			$base = trailingslashit( wp_upload_dir()['basedir'] );

			foreach ( $images as $rel ) {
				$full = $base . $rel;
				if ( file_exists( $full ) ) {
					wp_delete_file( $full );
				}
			}

			delete_comment_meta( $comment_id, '_sr_review_images' );
		}

		// ══════════════════════════════════════════════════════════════════
		// Admin: metabox on the comment edit screen
		// ══════════════════════════════════════════════════════════════════

		public function add_photos_meta_box( \WP_Comment $comment ): void {
			$images = get_comment_meta( $comment->comment_ID, '_sr_review_images', true );

			// Always show the box if photos exist, even if feature is now disabled.
			if ( ! self::is_enabled() && empty( $images ) ) {
				return;
			}

			add_meta_box(
				'sr-review-photos',
				esc_html__( 'Review Photos', 'stars-rating' ),
				array( $this, 'render_photos_meta_box' ),
				'comment',
				'normal',
				'default'
			);
		}

		public function render_photos_meta_box( \WP_Comment $comment ): void {
			$images = get_comment_meta( $comment->comment_ID, '_sr_review_images', true );
			?>
			<div class="sr-review-photos-admin">
				<?php if ( ! empty( $images ) && is_array( $images ) ) : ?>
					<p class="sr-photos-admin-count">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of photos */
								_n( '%d photo attached to this review.', '%d photos attached to this review.', count( $images ), 'stars-rating' ),
								count( $images )
							)
						);
						?>
					</p>
					<div class="sr-admin-photo-grid">
						<?php
						$base_url = trailingslashit( wp_upload_dir()['baseurl'] );
						$nonce    = wp_create_nonce( 'sr_delete_photo' );
						$ajax_url = esc_attr( admin_url( 'admin-ajax.php' ) );
						foreach ( $images as $index => $rel ) :
							$url = esc_url( $base_url . $rel );
							?>
							<div class="sr-admin-photo-item" data-index="<?php echo esc_attr( $index ); ?>">
								<a href="<?php echo $url; ?>" target="_blank" rel="noopener noreferrer">
									<img src="<?php echo $url; ?>" alt="" />
								</a>
								<button type="button"
									class="sr-admin-photo-delete"
									data-comment="<?php echo esc_attr( $comment->comment_ID ); ?>"
									data-index="<?php echo esc_attr( $index ); ?>"
									data-nonce="<?php echo esc_attr( $nonce ); ?>"
									data-ajax="<?php echo $ajax_url; ?>"
									title="<?php esc_attr_e( 'Delete photo', 'stars-rating' ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</div>
						<?php endforeach; ?>
					</div>
					<script>
					(function(){
						document.querySelectorAll('.sr-admin-photo-delete').forEach(function(btn){
							btn.addEventListener('click',function(){
								var self=this;
								if(!confirm('<?php echo esc_js( __( 'Delete this photo? This cannot be undone.', 'stars-rating' ) ); ?>')) return;
								var item=self.closest('.sr-admin-photo-item');
								var params=new URLSearchParams({
									action:      'sr_delete_review_photo',
									nonce:       self.dataset.nonce,
									comment_id:  self.dataset.comment,
									photo_index: self.dataset.index
								});
								fetch(self.dataset.ajax,{method:'POST',body:params,credentials:'same-origin'})
									.then(function(r){return r.json();})
									.then(function(r){if(r.success&&item){item.remove();}});
							});
						});
					})();
					</script>
				<?php else : ?>
					<p class="sr-comment-edit-none">
						<?php esc_html_e( 'No photos were uploaded with this review.', 'stars-rating' ); ?>
					</p>
				<?php endif; ?>
			</div>
			<?php
		}

		// ══════════════════════════════════════════════════════════════════
		// Lightbox shell rendered once in wp_footer
		// ══════════════════════════════════════════════════════════════════

		public function render_lightbox_shell(): void {
			if ( ! self::is_enabled() ) {
				return;
			}
			?>
			<div id="sr-lightbox" class="sr-lb" role="dialog" aria-modal="true"
				 aria-label="<?php esc_attr_e( 'Photo viewer', 'stars-rating' ); ?>"
				 tabindex="-1" hidden>
				<div class="sr-lb-overlay"></div>
				<button class="sr-lb-close" aria-label="<?php esc_attr_e( 'Close', 'stars-rating' ); ?>">&#x2715;</button>
				<div class="sr-lb-content">
					<button class="sr-lb-prev" aria-label="<?php esc_attr_e( 'Previous photo', 'stars-rating' ); ?>">&#8249;</button>
					<div class="sr-lb-img-wrap">
						<div class="sr-lb-spinner" aria-hidden="true"></div>
						<img class="sr-lb-img" src="" alt="" />
					</div>
					<button class="sr-lb-next" aria-label="<?php esc_attr_e( 'Next photo', 'stars-rating' ); ?>">&#8250;</button>
				</div>
				<div class="sr-lb-counter" aria-live="polite"></div>
			</div>
			<script>
			(function(){
				var lb = document.getElementById('sr-lightbox');
				if (!lb) return;
				var img     = lb.querySelector('.sr-lb-img');
				var spinner = lb.querySelector('.sr-lb-spinner');
				var counter = lb.querySelector('.sr-lb-counter');
				var btnPrev = lb.querySelector('.sr-lb-prev');
				var btnNext = lb.querySelector('.sr-lb-next');
				var items   = []; // Full-size URLs for the current review
				var current = 0;
				var touchX  = null;

				function open(gallery, idx) {
					var els = document.querySelectorAll('button.sr-lb-trigger[data-sr-gallery="' + gallery + '"]');
					items   = Array.prototype.map.call(els, function(el){ return el.dataset.src; });
					current = Math.max(0, Math.min(parseInt(idx, 10), items.length - 1));
					show();
					lb.removeAttribute('hidden');
					lb.focus();
					document.body.style.overflow = 'hidden';
				}

				function close() {
					lb.setAttribute('hidden', '');
					document.body.style.overflow = '';
					img.src = '';
					items   = [];
				}

				function show() {
					img.style.opacity = '0';
					spinner.style.display = '';
					var src = items[current];
					var tmp = new Image();
					tmp.onload = function() {
						img.src = src;
						img.style.opacity = '1';
						spinner.style.display = 'none';
					};
					tmp.onerror = function() { spinner.style.display = 'none'; };
					tmp.src = src;
					updateNav();
				}

				function prev() { current = (current - 1 + items.length) % items.length; show(); }
				function next() { current = (current + 1) % items.length; show(); }

				function updateNav() {
					var multi = items.length > 1;
					btnPrev.style.visibility = multi ? '' : 'hidden';
					btnNext.style.visibility = multi ? '' : 'hidden';
					counter.textContent = multi ? (current + 1) + ' / ' + items.length : '';
				}

				// Open on thumbnail click — event-delegated so it works for lazy-loaded comments.
				document.addEventListener('click', function(e) {
					var trigger = e.target.closest('button.sr-lb-trigger');
					if (trigger) {
						e.preventDefault();
						open(trigger.dataset.srGallery, trigger.dataset.index);
					}
				});

				// Close button and backdrop click.
				lb.querySelector('.sr-lb-close').addEventListener('click', close);
				lb.querySelector('.sr-lb-overlay').addEventListener('click', close);
				btnPrev.addEventListener('click', function(e){ e.stopPropagation(); prev(); });
				btnNext.addEventListener('click', function(e){ e.stopPropagation(); next(); });

				// Keyboard navigation.
				document.addEventListener('keydown', function(e) {
					if (lb.hasAttribute('hidden')) return;
					if (e.key === 'Escape')      { close(); }
					if (e.key === 'ArrowLeft')   { prev(); }
					if (e.key === 'ArrowRight')  { next(); }
				});

				// Touch swipe navigation.
				lb.addEventListener('touchstart', function(e) {
					touchX = e.touches[0].clientX;
				}, { passive: true });
				lb.addEventListener('touchend', function(e) {
					if (touchX === null) return;
					var diff = touchX - e.changedTouches[0].clientX;
					if (Math.abs(diff) > 50) { diff > 0 ? next() : prev(); }
					touchX = null;
				}, { passive: true });
			})();
			</script>
			<?php
		}

		// ══════════════════════════════════════════════════════════════════
		// Admin AJAX: delete a single photo
		// ══════════════════════════════════════════════════════════════════

		public function ajax_delete_single_photo(): void {
			check_ajax_referer( 'sr_delete_photo', 'nonce' );

			$comment_id = absint( $_POST['comment_id'] ?? 0 );

			if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'stars-rating' ) ) );
			}

			$index  = absint( $_POST['photo_index'] ?? 0 );
			$images = get_comment_meta( $comment_id, '_sr_review_images', true );

			if ( empty( $images ) || ! is_array( $images ) || ! isset( $images[ $index ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Photo not found.', 'stars-rating' ) ) );
			}

			$full = trailingslashit( wp_upload_dir()['basedir'] ) . $images[ $index ];
			if ( file_exists( $full ) ) {
				wp_delete_file( $full );
			}

			unset( $images[ $index ] );
			$images = array_values( $images ); // Re-index.

			if ( empty( $images ) ) {
				delete_comment_meta( $comment_id, '_sr_review_images' );
			} else {
				update_comment_meta( $comment_id, '_sr_review_images', $images );
			}

			wp_send_json_success();
		}
	}

	/**
	 * Returns the singleton instance of Stars_Rating_Review_Photos.
	 *
	 * @since 4.1.0
	 * @return Stars_Rating_Review_Photos
	 */
	function Stars_Rating_Review_Photos(): Stars_Rating_Review_Photos {
		return Stars_Rating_Review_Photos::instance();
	}

	Stars_Rating_Review_Photos();
}
