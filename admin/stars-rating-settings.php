<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Stars_Rating_Settings' ) ) :

    /**
     * Class Stars_Rating_Settings
     *
     * Plugin's settings class
     *
     * @since 1.0.0
     */
    final class Stars_Rating_Settings {

        /**
         * Single instance of Class.
         *
         * @var Stars_Rating_Settings
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
         * Stars_Rating_Settings constructor.
         * @since 1.0.0
         */
        public function __construct() {

            $this->init_hooks();

            // Stars Rating plugin settings loaded action hook
            do_action( 'Stars_Rating_Settings_loaded' );

        }

        public function init_hooks() {

            add_action( 'admin_init', array( $this, 'stars_rating_section' ) );
            add_action( 'init', array( $this, 'update_settings_field' ) );
        }

        public function stars_rating_section() {

            add_settings_section(
                'stars_rating_section',
                esc_html__( 'Stars Rating', 'stars-rating' ),
                array( $this, 'stars_rating_section_callback' ),
                'discussion'
            );

            add_settings_field(
                'enabled_post_types',
                esc_html__( 'Enabled Post Types', 'stars-rating' ),
                array( $this, 'enabled_post_types_callback' ),
                'discussion',
                'stars_rating_section',
                array(
                    'enabled_post_types'
                )
            );

            // register enabled_posts field
            register_setting( 'discussion','enabled_post_types', 'esc_attr' );
        }

        public function stars_rating_section_callback() {
            echo '<p class="description">'. esc_html__( 'Check the post types on which you want to enable stars rating feature.', 'stars-rating' ) .'</p>';
        }

        public function enabled_post_types_callback( $args ) {

            $enabled_posts = get_option(' enabled_post_types' );

            if ( ! is_array( $enabled_posts ) ) {
                $enabled_posts = (array) $enabled_posts;
            }

            $query = array(
                'public' => true
            );

            // get publicly registered post types
            $post_types = get_post_types( $query, 'names' );

            foreach ( $post_types  as $post_type ) {

                $checked = in_array( $post_type, $enabled_posts ) ? 'checked="checked"' : '';
                echo '<label for="'. $post_type .'"><input type="checkbox" id="'. $post_type .'" name="'. $args[0] .'[]" value="'. $post_type .'" '. $checked .'/>' . ucwords( $post_type ) . '</label><br>';
            }

        }

        public function update_settings_field() {
            add_filter( 'pre_update_option_enabled_post_types', array( $this, 'update_field_enabled_post_types' ), 10, 2 );
        }

        public function update_field_enabled_post_types( $new_value, $old_value ) {
            $new_value = $_POST['enabled_post_types'];
            return $new_value;
        }

    }

endif;


/**
 * Main instance of Stars_Rating_Settings.
 *
 * Returns the main instance of Stars_Rating_Settings to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Stars_Rating_Settings
 */
function Stars_Rating_Settings() {
    return Stars_Rating_Settings::instance();
}

// Get Stars_Rating_Settings Running.
Stars_Rating_Settings();