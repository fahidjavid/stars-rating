<?php
/**
 * Adds an "Avg. Rating" column to post/CPT list screens for all enabled post types,
 * and makes that column sortable (asc / desc) via a SQL JOIN on comment meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stars_Rating_Posts_Column' ) ) {

	class Stars_Rating_Posts_Column {

		/** @var Stars_Rating_Posts_Column */
		protected static $_instance;

		/**
		 * Prevents adding the sort-SQL filters more than once per request.
		 * @var bool
		 */
		private bool $sort_filters_added = false;

		public function __construct() {
			// Delay hook registration until admin_init so the enabled_post_types
			// option is reliably available and other plugins have registered CPTs.
			add_action( 'admin_init', array( $this, 'register_hooks' ) );
		}

		public static function instance(): self {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Dynamically register column hooks for every enabled post type.
		 */
		public function register_hooks(): void {
			$enabled = get_option( 'enabled_post_types', array( 'post', 'page' ) );
			$enabled = is_array( $enabled ) ? $enabled : (array) $enabled;

			foreach ( $enabled as $post_type ) {
				$post_type = sanitize_key( $post_type );
				if ( ! $post_type ) {
					continue;
				}
				add_filter( "manage_{$post_type}_posts_columns",        array( $this, 'add_column' ) );
				add_action( "manage_{$post_type}_posts_custom_column",  array( $this, 'render_column' ), 10, 2 );
				add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'register_sortable' ) );
			}

			add_action( 'pre_get_posts', array( $this, 'handle_sorting' ) );
		}

		/**
		 * Insert the "Avg. Rating" column right after the post title column.
		 */
		public function add_column( array $columns ): array {
			$inserted = array();
			foreach ( $columns as $key => $label ) {
				$inserted[ $key ] = $label;
				if ( 'title' === $key ) {
					$inserted['sr_avg_rating'] = sprintf(
						'<span class="dashicons dashicons-star-filled sr-col-icon" aria-hidden="true"></span><span class="sr-col-heading">%s</span>',
						esc_html__( 'Avg. Rating', 'stars-rating' )
					);
				}
			}
			return $inserted;
		}

		/**
		 * Render the average rating for a post using a single efficient SQL query.
		 */
		public function render_column( string $column, int $post_id ): void {
			if ( 'sr_avg_rating' !== $column ) {
				return;
			}

			global $wpdb;

			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						AVG( CAST( cm.meta_value AS DECIMAL(3,2) ) ) AS avg_rating,
						COUNT( cm.meta_value )                        AS total
					FROM {$wpdb->comments} c
					INNER JOIN {$wpdb->commentmeta} cm
						ON cm.comment_id = c.comment_ID
						AND cm.meta_key  = 'rating'
					WHERE c.comment_post_ID = %d
					  AND c.comment_approved = '1'",
					$post_id
				)
			);

			if ( empty( $result ) || ! $result->total ) {
				echo '<span class="sr-col-empty">' . esc_html__( 'No ratings', 'stars-rating' ) . '</span>';
				return;
			}

			$avg   = round( (float) $result->avg_rating, 1 );
			$total = absint( $result->total );

			echo '<div class="sr-col-cell">';
			echo '<div class="sr-col-row">';
			echo '<span class="sr-col-score">' . esc_html( $avg ) . '</span>';
			echo wp_kses_post( Stars_Rating::get_rating_stars_markup( $avg ) );
			echo '</div>';
			echo '<span class="sr-col-count">' . esc_html(
				sprintf(
					/* translators: %d number of reviews */
					_n( '%d review', '%d reviews', $total, 'stars-rating' ),
					$total
				)
			) . '</span>';

			// Optionally show like/dislike counts if that feature is enabled for this post type.
			$likes_types = (array) get_option( 'sr_likes_post_types', array( 'post', 'page' ) );
			if ( 'enable' === get_option( 'sr_likes_enabled', 'disable' )
				&& in_array( get_post_type( $post_id ), $likes_types, true ) ) {
				$likes    = absint( get_post_meta( $post_id, '_sr_likes',    true ) );
				$dislikes = absint( get_post_meta( $post_id, '_sr_dislikes', true ) );
				echo '<span class="sr-col-likes">';
				echo '<span class="sr-col-like-up" title="' . esc_attr__( 'Likes', 'stars-rating' ) . '">&#128077; ' . absint( $likes ) . '</span>';
				echo '<span class="sr-col-like-sep">·</span>';
				echo '<span class="sr-col-like-dn" title="' . esc_attr__( 'Dislikes', 'stars-rating' ) . '">&#128078; ' . absint( $dislikes ) . '</span>';
				echo '</span>';
			}

			echo '</div>';
		}

		/**
		 * Declare the column as sortable so WordPress renders clickable column headers.
		 */
		public function register_sortable( array $columns ): array {
			$columns['sr_avg_rating'] = 'sr_avg_rating';
			return $columns;
		}

		/**
		 * When the admin requests orderby=sr_avg_rating, attach SQL modifier filters
		 * to sort posts by their average comment rating.
		 */
		public function handle_sorting( \WP_Query $query ): void {
			if ( ! is_admin() || ! $query->is_main_query() ) {
				return;
			}

			if ( 'sr_avg_rating' !== $query->get( 'orderby' ) ) {
				return;
			}

			// Ensure the post type has ratings enabled.
			$post_type = $query->get( 'post_type' );
			$enabled   = (array) get_option( 'enabled_post_types', array( 'post', 'page' ) );
			if ( ! in_array( $post_type, $enabled, true ) ) {
				return;
			}

			if ( ! $this->sort_filters_added ) {
				$this->sort_filters_added = true;
				add_filter( 'posts_join',    array( $this, 'sort_join' ),    10, 2 );
				add_filter( 'posts_groupby', array( $this, 'sort_groupby' ), 10, 2 );
				add_filter( 'posts_orderby', array( $this, 'sort_orderby' ), 10, 2 );
			}
		}

		/**
		 * LEFT JOIN comments + commentmeta so we can AVG() the rating per post.
		 * A LEFT JOIN keeps posts that have zero ratings in the result set.
		 */
		public function sort_join( string $join, \WP_Query $query ): string {
			global $wpdb;
			$join .= " LEFT JOIN {$wpdb->comments} sr_c
					ON  sr_c.comment_post_ID = {$wpdb->posts}.ID
					AND sr_c.comment_approved = '1' ";
			$join .= " LEFT JOIN {$wpdb->commentmeta} sr_cm
					ON  sr_cm.comment_id = sr_c.comment_ID
					AND sr_cm.meta_key   = 'rating' ";
			return $join;
		}

		/**
		 * GROUP BY post ID so AVG() aggregates per post, not per comment row.
		 */
		public function sort_groupby( string $groupby, \WP_Query $query ): string {
			global $wpdb;
			return "{$wpdb->posts}.ID";
		}

		/**
		 * ORDER BY the computed average.
		 * Posts with no ratings (NULL average) are always sorted to the very end,
		 * regardless of ASC or DESC direction.
		 */
		public function sort_orderby( string $orderby, \WP_Query $query ): string {
			$direction = strtoupper( $query->get( 'order' ) ) === 'ASC' ? 'ASC' : 'DESC';
			// (IS NULL) ASC pushes NULLs after all rated posts in both sort directions.
			return "( AVG( CAST( sr_cm.meta_value AS DECIMAL(3,2) ) ) IS NULL ) ASC,
					AVG( CAST( sr_cm.meta_value AS DECIMAL(3,2) ) ) {$direction}";
		}
	}

	/**
	 * Returns the singleton instance.
	 *
	 * @since 4.0.8
	 * @return Stars_Rating_Posts_Column
	 */
	function Stars_Rating_Posts_Column(): Stars_Rating_Posts_Column {
		return Stars_Rating_Posts_Column::instance();
	}

	Stars_Rating_Posts_Column();
}
