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
		wp_enqueue_style( 'sis_css', SIS_URL.'/assets/css/sis-style.css', array( 'jquery-ui-sis' ), SIS_VERSION );
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
			'reading' 			=> __( 'Reading attachments...', 'sis' ),
			'maximumWidth' 		=> __( 'Maximum width', 'sis' ),
			'maximumHeight' 	=> __( 'Maximum height', 'sis' ),
			'crop' 				=> __( 'Crop ?', 'sis' ),
			'tr' 				=> __( 'yes', 'sis' ),
			'fl'				=> __( 'no', 'sis' ),
			'show'				=> __( 'Show in post insertion ?', 'sis' ),
			'of' 				=> __( ' of ', 'sis' ),
			'or' 				=> __( ' or ', 'sis' ),
			'beforeEnd' 		=> __( ' before the end.', 'sis' ),
			'deleteImage' 		=> __( 'Delete', 'sis' ),
			'noMedia' 			=> __( 'No media in your site to regenerate !', 'sis' ),
			'regenerating' 		=> __( 'Regenerating ', 'sis'),
			'regenerate' 		=> __( 'Regenerate ', 'sis'),
			'validate' 			=> __( 'Validate image size name', 'sis' ),
			'done' 				=> __( 'Done.', 'sis' ),
			'size' 				=> __( 'Size', 'sis' ),	
			'notOriginal' 		=> __( 'Don\'t use the basic Wordpress thumbnail size name, use the form above to edit them', 'sis' ),
			'alreadyPresent' 	=> __( 'This size is already registered, edit it instead of recreating it.', 'sis' ),
			'confirmDelete' 	=> __( 'Do you really want to delete these size ?', 'sis' ),
			'update' 			=> __( 'Update', 'sis' ),
			'ajaxErrorHandler' 	=> __( 'Error requesting page', 'sis' ),
			'messageRegenerated' => __( 'images have been regenerated !', 'sis' ),
			'validateButton' 	=> __( 'Validate', 'sis' ),
			'startedAt' 		=> __( ' started at', 'sis' ),
			'customName'		=> __( 'Public name', 'sis' ),
			'finishedAt' 		=> __( ' finished at :', 'sis' ),
			'phpError' 			=> __( 'Error during the php treatment, be sure to not have php errors in your page', 'sis' ),
			'notSaved' 			=> __( 'All the sizes you have modifed are not saved, continue anyway ?', 'sis' ),
			'soloRegenerated'	=> __( 'This image has been regenerated in %s seconds', 'sis' ),
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
}