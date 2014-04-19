<?php
Class SIS_Admin_Main {

	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ? '' : '.min' ;
		// Add javascript
		wp_register_script( 'underscore', '//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min.js' , array(), '1.6.0' );
		wp_register_script( 'sis_js', SIS_URL.'/assets/js/sis'.$suffix.'.js', array( 'jquery', 'jquery-ui-button', 'jquery-ui-progressbar', 'underscore' ), SIS_VERSION );

		// Differencitate the scripts
		wp_register_script( 'sis_js_attachments', SIS_URL.'/assets/js/sis-attachments'.$suffix.'.js', array( 'jquery' ), SIS_VERSION );
		
		// Add javascript translation
		wp_localize_script( 'sis_js', 'sis', self::localize_vars() );
		wp_localize_script( 'sis_js_attachments', 'sis', self::localize_vars() );
			
		// Add CSS
		wp_enqueue_style( 'sis_css', SIS_URL.'/assets/css/sis-style.css', array(), SIS_VERSION );
	}


	/**
	 * Localize the var for javascript
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function localize_vars() {
		return array(
			'ajaxUrl' 			=>  admin_url( '/admin-ajax.php' ),
			'reading' 			=> __( 'Reading attachments...', 'simple-image-sizes' ),
			'maximumWidth' 		=> __( 'Maximum width', 'simple-image-sizes' ),
			'maximumHeight' 	=> __( 'Maximum height', 'simple-image-sizes' ),
			'crop' 				=> __( 'Crop ?', 'simple-image-sizes' ),
			'tr' 				=> __( 'yes', 'simple-image-sizes' ),
			'fl'				=> __( 'no', 'simple-image-sizes' ),
			'show'				=> __( 'Show in post insertion ?', 'simple-image-sizes' ),
			'of' 				=> __( ' of ', 'simple-image-sizes' ),
			'or' 				=> __( ' or ', 'simple-image-sizes' ),
			'beforeEnd' 		=> __( ' before the end.', 'simple-image-sizes' ),
			'deleteImage' 		=> __( 'Delete', 'simple-image-sizes' ),
			'noMedia' 			=> __( 'No media in your site to regenerate !', 'simple-image-sizes' ),
			'regenerating' 		=> __( 'Regenerating ', 'simple-image-sizes'),
			'regenerate' 		=> __( 'Regenerate ', 'simple-image-sizes'),
			'validate' 			=> __( 'Validate image size name', 'simple-image-sizes' ),
			'done' 				=> __( 'Done.', 'simple-image-sizes' ),
			'size' 				=> __( 'Size', 'simple-image-sizes' ),	
			'notOriginal' 		=> __( 'Don\'t use the basic Wordpress thumbnail size name, use the form above to edit them', 'simple-image-sizes' ),
			'alreadyPresent' 	=> __( 'This size is already registered, edit it instead of recreating it.', 'simple-image-sizes' ),
			'confirmDelete' 	=> __( 'Do you really want to delete these size ?', 'simple-image-sizes' ),
			'update' 			=> __( 'Update', 'simple-image-sizes' ),
			'ajaxErrorHandler' 	=> __( 'Error requesting page', 'simple-image-sizes' ),
			'messageRegenerated' => __( 'images have been regenerated !', 'simple-image-sizes' ),
			'validateButton' 	=> __( 'Validate', 'simple-image-sizes' ),
			'startedAt' 		=> __( ' started at', 'simple-image-sizes' ),
			'customName'		=> __( 'Public name', 'simple-image-sizes' ),
			'finishedAt' 		=> __( ' finished at :', 'simple-image-sizes' ),
			'phpError' 			=> __( 'Error during the php treatment, be sure to not have php errors in your page', 'simple-image-sizes' ),
			'notSaved' 			=> __( 'All the sizes you have modifed are not saved, continue anyway ?', 'simple-image-sizes' ),
			'soloRegenerated'	=> __( 'This image has been regenerated in %s seconds', 'simple-image-sizes' ),
			'crop_positions'	=>  self::get_available_crop(),
			'regen_one'			=> wp_create_nonce( 'regen' )
		);
	}

	public static function add_template() {
		global $pagenow;
		if( $pagenow != 'options-media.php' ) {
			return false;
		}
		
		if( is_file( SIS_DIR.'/templates/admin-js.html' ) ) {
			include( SIS_DIR.'/templates/admin-js.html' );
		}

		return true;
	}

	public static function get_available_crop() {
		$x = array(
			'left' => __( 'left', 'simple-image-sizes' ),
			'center' => __( 'center', 'simple-image-sizes' ),
			'right' => __( 'right', 'simple-image-sizes' ),
		);

		$y = array(
			'top' => __( 'top', 'simple-image-sizes' ),
			'center' => __( 'center', 'simple-image-sizes' ),
			'bottom'  => __( 'bottom', 'simple-image-sizes' ),
		);

		$crops = array();
		foreach ( $x as $x_pos => $x_pos_label ) {
			foreach ( $y as $y_pos => $y_pos_label ) {
				$crops[$x_pos.'_'.$y_pos] = $x_pos_label.' '.$y_pos_label;
			}
		}

		return $crops;
	}

	public static function is_crop_position( $crop_position = '' ) {
		$crops = self::get_available_crop();
		return isset( $crops[$crop_position] );
	}

	public static function get_crop_position_label( $crop_position = '' ) {
		if( !self::is_crop_position( $crop_position ) ) {
			return '';
		}
		$crops = self::get_available_crop();
		return $crops[$crop_position];
	}
}