<?php
Class SISClient {
	
	// Set the original
	var $original = array( 'thumbnail', 'medium', 'large' );

	function __construct() {
		add_action ( 'init', array( __CLASS__, 'init' ) );
	}
	
	/**
	 * Override the images by the plugin images
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function init() {
		// Get inital options
		$sizes = get_option( SIS_OPTION, array() );
		
		// Return flase if empty
		if( empty( $sizes ) || !is_array( $sizes ) ) {
			return false;
		}
		
		// Set the new sizes
		foreach( $sizes as $name => $size ) {
			if( empty( $size ) || !isset( $size['w'] ) || !isset( $size['h'] ) ) {
				continue;
			}
			// Add the images sizes
			add_image_size( $name, $size['w'], $size['h'], ( isset( $size['c'] ) && !empty( $size['c'] ) )? $size['c'] : 0 );
		}
	}
}