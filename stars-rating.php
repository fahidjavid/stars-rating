<?php
/**
 * Plugin Name: Stars Rating
 * Plugin URI: https://wordpress.org/plugins/stars-rating/
 * Description: A plugin to turn comments into reviews by adding rating feature.
 * Version: 3.5.5
 * Author: Fahid Javid
 * Author URI: https://www.fahidjavid.com
 * Text Domain: stars-rating
 * Domain Path: languages
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'stars_rating_load_textdomain' ) ) {
	/**
	 * Load text domain for translation.
	 * @since 1.0.0
	 */
	function stars_rating_load_textdomain() {
		load_plugin_textdomain( 'stars-rating', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	// load text domain for translation.
	stars_rating_load_textdomain();
}


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/stars-rating-include.php';

/**
 * Main instance of Stars_Rating.
 *
 * Returns the main instance of Stars_Rating to prevent the need to use globals.
 *
 * @return Stars_Rating
 * @since  1.0.0
 */
function Stars_Rating() {
	return Stars_Rating::instance();
}

// Get Stars_Rating Running.
Stars_Rating();