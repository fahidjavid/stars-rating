<?php

class Stars_Rating_Comments_Column {

	/**
	 * Single instance of Class.
	 *
	 * @since 4.0.0
	 * @var Stars_Rating_Comments_Column
	 */
	protected static $_instance;

	public function __construct() {
		$this->init_hooks();
	}

	public function init_hooks() {
		add_filter( 'manage_edit-comments_columns', array( $this, 'add_rating_column' ) );
		add_action( 'manage_comments_custom_column', array( $this, 'display_rating_column_value' ), 10, 2 );
	}

	/**
	 * Provides singleton instance.
	 *
	 * @since 4.0.0
	 * @return self instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add custom stars rating column to the comments page.
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function add_rating_column( $columns ) {

		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( $key === 'comment' ) {
				// Insert the custom column after the 'comment' column
				$new_columns['rating'] = esc_html__( 'Stars Rating', 'stars-rating' );
			}
		}

		return $new_columns;
	}

	/**
	 * Display rating meta field value in added column
	 *
	 * @param $column_name
	 * @param $comment_ID
	 *
	 * @return void
	 */
	public function display_rating_column_value( $column_name, $comment_ID ) {
		if ( $column_name === 'rating' ) {
			$custom_meta_value = get_comment_meta( $comment_ID, 'rating', true );

			echo Stars_Rating::get_rating_stars_markup( $custom_meta_value );
		}
	}

}

/**
 * Returns the main instance of Stars_Rating_Comments_Column to prevent the need to use globals.
 *
 * @since  4.0.0
 * @return Stars_Rating_Comments_Column
 */
function Stars_Rating_Comments_Column() {
	return Stars_Rating_Comments_Column::instance();
}

// Get Stars_Rating_Comments_Column Running.
Stars_Rating_Comments_Column();
