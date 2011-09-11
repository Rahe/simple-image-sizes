<?php
Class SISClient {
	
	// Set the original
	var $original = array( 'thumbnail', 'medium', 'large' );

	function __construct() {
		add_action ( 'init', array( &$this, 'init' ) );
	}
	
	/**
	 * Override the images by the plugin images
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	function init() {
		// Get inital options
		$sizes = get_option( 'custom_image_sizes' );
		
		// Return flase if empty
		if( empty( $sizes ) || !is_array( $sizes ) )
			return false;
		
		// Set the new sizes
		foreach( $sizes as $name => $size ){
			// Get cropping
			$crop = ( isset( $size['c'] ) && !empty( $size['c'] ) )? $size['c'] : 0 ;

			// Add the images sizes
			add_image_size( $name, $size['w'], $size['h'], $crop );
		}		
	}
}
?>