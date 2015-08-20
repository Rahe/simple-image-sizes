<?php

Class SIS_Client {

	// Set the original
	var $original = array( 'thumbnail', 'medium', 'large' );

	function __construct() {
		// Make new image sizes
		add_action( 'init', array( __CLASS__, 'after_setup_theme' ), 1 );

		// Add translation
		add_action( 'init', array( __CLASS__, 'init_translation' ), 2 );
	}

	/**
	 * Override the images by the plugin images
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function after_setup_theme() {
		// Get inital options
		$sizes = get_option( SIS_OPTION, array() );

		// Return false if empty
		if ( empty( $sizes ) || ! is_array( $sizes ) ) {
			return;
		}

		// Set the new sizes
		foreach ( $sizes as $name => $size ) {
			if ( empty( $size ) || ! isset( $size['w'] ) || ! isset( $size['h'] ) ) {
				continue;
			}

			$crop = ( isset( $size['c'] ) && ! empty( $size['c'] ) ) ? $size['c'] : false;

			if ( is_string( $crop ) ) {
				$crop = explode( '_', $crop );
			}

			// Add the images sizes
			add_image_size( $name, $size['w'], $size['h'], $crop );
		}
	}

	/**
	 * Load the plugin text domain
	 *
	 * @param void
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function init_translation() {
		load_plugin_textdomain( 'simple-image-sizes', false, basename( rtrim( SIS_DIR, '/' ) ) . '/languages' );
	}
}