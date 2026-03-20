<?php
/**
 * Manages star-rating integration with the WordPress admin comments UI:
 *  - "Stars Rating" column on the comments list table (with asc/desc sorting).
 *  - Read-only rating display on the comment edit screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stars_Rating_Comments_Column' ) ) {

	class Stars_Rating_Comments_Column {

		/** @var Stars_Rating_Comments_Column */
		protected static $_instance;

		public function __construct() {
			$this->init_hooks();
		}

		public static function instance(): self {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function init_hooks(): void {
			// ── Comments list table ───────────────────────────────────────────
			add_filter( 'manage_edit-comments_columns',          array( $this, 'add_rating_column' ) );
			add_action( 'manage_comments_custom_column',         array( $this, 'display_rating_column_value' ), 10, 2 );
			add_filter( 'manage_edit-comments_sortable_columns', array( $this, 'register_sortable_column' ) );
			add_action( 'pre_get_comments',                      array( $this, 'handle_comment_sorting' ) );

			// ── Comment edit screen ───────────────────────────────────────────
			add_action( 'add_meta_boxes_comment', array( $this, 'add_rating_meta_box' ) );
		}

		// ══════════════════════════════════════════════════════════════════════
		// Comments List — Column
		// ══════════════════════════════════════════════════════════════════════

		/**
		 * Insert "Stars Rating" column right after the "Comment" column.
		 */
		public function add_rating_column( array $columns ): array {
			$new_columns = array();
			foreach ( $columns as $key => $value ) {
				$new_columns[ $key ] = $value;
				if ( 'comment' === $key ) {
					$new_columns['rating'] = sprintf(
						'<span class="dashicons dashicons-star-filled sr-col-icon" aria-hidden="true"></span><span class="sr-col-heading">%s</span>',
						esc_html__( 'Stars Rating', 'stars-rating' )
					);
				}
			}
			return $new_columns;
		}

		/**
		 * Render the star rating for a comment row.
		 */
		public function display_rating_column_value( string $column_name, int $comment_ID ): void {
			if ( 'rating' !== $column_name ) {
				return;
			}

			$rating = get_comment_meta( $comment_ID, 'rating', true );

			if ( '' === $rating || false === $rating ) {
				echo '<span class="sr-col-empty">' . esc_html__( 'No rating', 'stars-rating' ) . '</span>';
				return;
			}

			echo '<div class="sr-col-cell">';
			echo '<div class="sr-col-row">';
			echo '<span class="sr-col-score">' . esc_html( $rating ) . '</span>';
			echo wp_kses_post( Stars_Rating::get_rating_stars_markup( (int) $rating ) );
			echo '</div>';
			echo '</div>';
		}

		// ══════════════════════════════════════════════════════════════════════
		// Comments List — Sorting
		// ══════════════════════════════════════════════════════════════════════

		/**
		 * Declare the "rating" column as sortable.
		 */
		public function register_sortable_column( array $columns ): array {
			$columns['rating'] = 'rating';
			return $columns;
		}

		/**
		 * Modify the comment query when admin requests sort by star rating.
		 *
		 * Uses a named meta_query clause so WP_Comment_Query can sort by it while
		 * still including comments that have no rating (LEFT JOIN behaviour via the
		 * OR relation).
		 */
		public function handle_comment_sorting( \WP_Comment_Query $query ): void {
			if ( ! is_admin() ) {
				return;
			}

			// WordPress translates the URL ?orderby=rating into the query.
			$orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : '';

			if ( 'rating' !== $orderby ) {
				return;
			}

			$order = ( isset( $_GET['order'] ) && 'asc' === strtolower( $_GET['order'] ) ) ? 'ASC' : 'DESC';

			// OR relation so comments without a rating are still returned
			// (they sort as NULL → end of list in both directions).
			$query->query_vars['meta_query'] = array(
				'relation'      => 'OR',
				'rating_clause' => array(
					'key'     => 'rating',
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				),
				'no_rating'     => array(
					'key'     => 'rating',
					'compare' => 'NOT EXISTS',
				),
			);

			// Sort by the named clause; comments without a rating get NULL
			// which MySQL naturally places at the end for DESC, and first for ASC.
			// We flip the direction for ASC so no-rating comments still appear last.
			$query->query_vars['orderby'] = array( 'rating_clause' => $order );
		}

		// ══════════════════════════════════════════════════════════════════════
		// Comment Edit Screen
		// ══════════════════════════════════════════════════════════════════════

		/**
		 * Register the rating meta box on the comment edit screen.
		 *
		 * @param \WP_Comment $comment The comment being edited.
		 */
		public function add_rating_meta_box( \WP_Comment $comment ): void {
			// Only show the box when a rating actually exists for this comment.
			$rating = get_comment_meta( $comment->comment_ID, 'rating', true );

			add_meta_box(
				'sr-comment-rating',
				esc_html__( 'Stars Rating', 'stars-rating' ),
				array( $this, 'render_rating_meta_box' ),
				'comment',
				'normal',
				'high'
			);
		}

		/**
		 * Render the rating meta box on the comment edit screen.
		 *
		 * @param \WP_Comment $comment The comment being edited.
		 */
		public function render_rating_meta_box( \WP_Comment $comment ): void {
			$rating = get_comment_meta( $comment->comment_ID, 'rating', true );
			?>
			<div class="sr-comment-edit-rating">
				<?php if ( '' !== $rating && false !== $rating ) : ?>
					<div class="sr-comment-edit-stars">
						<?php echo wp_kses_post( Stars_Rating::get_rating_stars_markup( (int) $rating ) ); ?>
						<span class="sr-comment-edit-value">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s star rating value e.g. "4/5" */
									__( '%s out of 5', 'stars-rating' ),
									$rating
								)
							);
							?>
						</span>
					</div>
				<?php else : ?>
					<p class="sr-comment-edit-none">
						<?php esc_html_e( 'No star rating was submitted with this comment.', 'stars-rating' ); ?>
					</p>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Returns the singleton instance.
	 *
	 * @since 4.0.0
	 * @return Stars_Rating_Comments_Column
	 */
	function Stars_Rating_Comments_Column(): Stars_Rating_Comments_Column {
		return Stars_Rating_Comments_Column::instance();
	}

	Stars_Rating_Comments_Column();
}
