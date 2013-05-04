<?php
Class SISAdmin {
	
	// Original sizes
	public static $original = array( 'thumbnail', 'medium', 'large' );

	public function __construct(){
		// Init
		add_action ( 'admin_menu', array( &$this, 'init' ) );
		add_action ( 'admin_enqueue_scripts', array( __CLASS__, 'registerScripts' ), 11 );
		
		// Add underscore template
		add_action( 'admin_footer', array( __CLASS__, 'addTemplate' ) );
		
		// Add ajax action
		// Option page
		add_action( 'wp_ajax_'.'sis_get_list', array( __CLASS__, 'a_GetList' ) );
		add_action( 'wp_ajax_'.'sis_rebuild_image', array( __CLASS__, 'a_ThumbnailRebuild' ) );
		add_action( 'wp_ajax_'.'sis_get_sizes', array( __CLASS__, 'a_GetSizes' ) );
		add_action( 'wp_ajax_'.'sis_add_size', array( __CLASS__, 'a_AddSize' ) );
		add_action( 'wp_ajax_'.'sis_remove_size', array( __CLASS__, 'a_RemoveSize' ) );
		
		// Add image sizes in the form, check if 3.3 is installed or not
		if( !function_exists( 'is_main_query' ) ) {
			add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'sizesInForm' ), 11, 2 ); // Add our sizes to media forms
		} else {
			add_filter( 'image_size_names_choose', array( __CLASS__, 'AddThumbnailName' ) );
		}
		
		// Add link in plugins list
		add_filter( 'plugin_action_links', array( __CLASS__,'addSettingsLink' ), 10, 2 );
		
		// Add action in media row quick actions
		add_filter( 'media_row_actions', array( __CLASS__, 'addActionsList' ), 10, 2 );
		
		// Add filter for the Media single
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'addFieldRegenerate' ), 9, 2 );
		
	}
	
	/**
	 * Register javascripts and css.
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function registerScripts( $hook_suffix = '' ) {
		if( !isset( $hook_suffix ) || empty( $hook_suffix ) ) {
			return false;
		}
		
		if( $hook_suffix == 'options-media.php' ) {
			// Add javascript
			wp_enqueue_script( 'underscore', 'http://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.4.3/underscore-min.js' , array(), '1.4.3' );
			wp_enqueue_script( 'sis_js', SIS_URL.'/js/sis.min.js', array( 'jquery', 'jquery-ui-button', 'jquery-ui-progressbar', 'underscore' ), SIS_VERSION );
			
			// Add CSS
			wp_enqueue_style( 'jquery-ui-sis', SIS_URL.'/css/Aristo/jquery-ui-1.8.7.custom.css', array(), '1.8.7' );
			wp_enqueue_style( 'sis_css', SIS_URL.'/css/sis-style.css', array(), SIS_VERSION );
		} elseif( $hook_suffix == 'upload.php' || ( $hook_suffix == 'post.php' && isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) ) {
			// Add javascript
			wp_enqueue_script( 'sis_js', SIS_URL.'/js/sis-attachments.min.js', array( 'jquery' ), SIS_VERSION );
		}
		
		// Add javascript translation
		wp_localize_script( 'sis_js', 'sis', self::localizeVars() );
	}
	
	/**
	 * Localize the var for javascript
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function localizeVars() {
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

	public static function addTemplate() {
		global $pagenow;
		if( $pagenow != 'options-media.php' ) {
			return false;
		}
		
		if( is_file( SIS_DIR.'/templates/admin-js.html' ) ) {
			include( SIS_DIR.'/templates/admin-js.html' );
		}

		return true;
	}
	
	/**
	 * Add action in media row
	 * 
	 * @since 2.2
	 * @access public
	 * @return $actions : array of actions and content to display
	 * @author Nicolas Juen
	 */
	public static function addActionsList( $actions, $object ) {
		
		// Add action for regeneration
		$actions['sis-regenerate'] = "<a href='#' data-id='".$object->ID."' class='sis-regenerate-one'>".__( 'Regenerate thumbnails', 'sis' )."</a>";
		
		// Return actions
		return $actions;
	}
	
	/**
	 * Add a link to the setting option page
	 * 
	 * @access public
 	 * @param array $links
	 * @param string $file
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function addSettingsLink( $links, $file ) {
	
		if( $file != 'simple-image-sizes/simple_image_sizes.php' ) {
			return $links;
		}
			
		$settings_link = '<a href="'.admin_url('options-media.php').'"> '.__( 'Settings', 'sis' ).' </a>';
		array_unshift( $links, $settings_link );
		
		return $links;
	}

	/**
	 * Init for the option page
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	function init() {
		// Check if admin
		if( !is_admin() ) {
			return false;
		}
		
		// Get the image sizes
		global $_wp_additional_image_sizes;
		$options = get_option( SIS_OPTION );

		// Get the sizes and add the settings
		foreach ( get_intermediate_image_sizes() as $s ) {
			// Don't make the original sizes or numeric sizes that appear
			if( in_array( $s, self::$original ) || is_integer( $s ) ) {
				continue;
			}
			
			// Set width
			$width = isset( $_wp_additional_image_sizes[$s]['width'] ) ? intval( $_wp_additional_image_sizes[$s]['width'] ) : get_option( "{$s}_size_w" ) ;
			
			// Set height
			$height = isset( $_wp_additional_image_sizes[$s]['height'] ) ? intval( $_wp_additional_image_sizes[$s]['height'] ) : get_option( "{$s}_size_h" ) ;
			
			//Set crop
			$crop = isset( $_wp_additional_image_sizes[$s]['crop'] ) ? intval( $_wp_additional_image_sizes[$s]['crop'] ) : get_option( "{$s}_crop" ) ;
			
			// Add the setting field for this size
			add_settings_field( 'image_size_'.$s, sprintf( __( '%s size', 'sis' ), $s ), array( &$this, 'imageSizes' ), 'media' , 'default', array( 'name' => $s , 'width' => $width , 'height' => $height, 'c' => $crop ) );
		}

		// Register the setting for media option page
		register_setting( 'media', SIS_OPTION );

		// Add the button
		add_settings_field( 'add_size_button', __( 'Add a new size', 'sis' ), array( &$this, 'addSizeButton' ), 'media' );

		// Add php button
		add_settings_field( 'get_php_button', __( 'Get php for theme', 'sis' ), array( &$this, 'getPhpButton' ), 'media' );

		// Add section for the thumbnail regeneration
		add_settings_section( 'thumbnail_regenerate', __( 'Thumbnail regeneration', 'sis' ), array( &$this, 'thumbnailRegenerate' ), 'media' );
 	}
 	
 	/**
 	 * Display the row of the image size
 	 * 
 	 * @access public
 	 * @param mixed $args
 	 * @return void
	 * @author Nicolas Juen
 	 */
 	public function imageSizes( $args ) {
 		
		if( is_integer( $args['name'] ) )
			return false;
		
 		// Get the options
		$sizes = (array)get_option( SIS_OPTION, array() );
		
		// Get the vars
		$height 	=	isset( $sizes[$args['name']]['h'] )? $sizes[$args['name']]['h'] : $args['height'] ;
		$width 		=	isset( $sizes[$args['name']]['w'] )? $sizes[$args['name']]['w'] : $args['width'] ;
		$crop 		=	isset( $sizes[$args['name']]['c'] ) && !empty( $sizes[$args['name']]['c'] )? $sizes[$args['name']]['c'] : $args['c'] ;
		$show 		=	isset( $sizes[$args['name']]['s'] ) && !empty( $sizes[$args['name']]['s'] )? '1' : '0' ;
		$custom 	=	isset( $sizes[$args['name']]['custom'] ) && !empty( $sizes[$args['name']]['custom'] )? '1' : '0' ;
		$name 		=	isset( $sizes[$args['name']]['n'] ) && !empty( $sizes[$args['name']]['n'] )? esc_html( $sizes[$args['name']]['n'] ) : esc_html( $args['name'] ) ;
		
		?>
		<input type="hidden" value="<?php echo esc_attr( $args['name'] ); ?>" name="image_name" />
		<?php if( $custom ): ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][custom]' ); ?>" type="hidden" id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][custom]' ); ?>" value="1" />
		<?php else: ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][theme]' ); ?>" type="hidden" id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][theme]' ); ?>" value="1" />
		<?php endif; ?>
		<label class="sis-label" for="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][w]' ); ?>">
			<?php _e( 'Maximum width', 'sis'); ?> 
			<input name="<?php esc_attr_e( 'custom_image_sizes['.$args['name'].'][w]' ); ?>" class='w small-text' type="number" step='1' min='0' id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][w]' ); ?>" base_w='<?php echo esc_attr( $width ); ?>' value="<?php echo esc_attr( $width ); ?>" />
		</label>
		<label class="sis-label" for="<?php  esc_attr_e( 'custom_image_sizes['.$args['name'].'][h]' ); ?>">
			<?php _e( 'Maximum height', 'sis'); ?> 
			<input name="<?php esc_attr_e( 'custom_image_sizes['.$args['name'].'][h]' ); ?>" class='h small-text' type="number" step='1' min='0' id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][h]' ); ?>" base_h='<?php echo esc_attr( $height ); ?>' value="<?php echo esc_attr( $height ); ?>" />
		</label>
		<label class="sis-label" for="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][n]' ); ?>">
			<?php _e( 'Public name', 'sis'); ?> 
			<input name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][n]' ); ?>" class='n' type="text" id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][n]' ); ?>" base_n='<?php echo $name; ?>' value="<?php echo $name ?>" />
		</label>
		<span class="size_options">
			<input type='checkbox' id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][c]' ); ?>" <?php checked( $crop, 1 ) ?> class="c crop" base_c='<?php echo esc_attr( $crop ); ?>' name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][c]' ); ?>" value="1" />
			<label class="c" for="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][c]' ); ?>"><?php _e( 'Crop ?', 'sis'); ?></label>
			
			<input type='checkbox' id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][s]'); ?>" <?php checked( $show, 1 ) ?> class="s show" base_s='<?php echo esc_attr( $show ); ?>' name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][s]'); ?>" value="1" />
			<label class="s" for="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][s]'); ?>"><?php _e( 'Show in post insertion ?', 'sis'); ?></label>
		</span>
		<span class="delete_size"><?php _e( 'Delete', 'sis'); ?></span>
		<span class="add_size validate_size"><?php _e( 'Update', 'sis'); ?></span>
		
		<input type="hidden" class="deleteSize" value='<?php echo wp_create_nonce( 'delete_'.$args['name'] ); ?>' />
	<?php }
	
	/**
	 * Add the button to add a size
	 * 
	 * @access public
	 * @return void
 	 * @author Nicolas Juen
	 */
	public function addSizeButton() { ?>
		<input type="button" class="button-secondary action" id="add_size" value="<?php esc_attr_e( 'Add a new size of thumbnail', 'sis'); ?>" />
	<?php
	}	
	
	/**
	 * Add the button to get the php for th sizes
	 * 
	 * @access public
	 * @return void
 	 * @author Nicolas Juen
	 */
	public function getPhpButton() { ?>
		<input type="button" class="button-secondary action" id="get_php" value="<?php esc_attr_e( 'Get the PHP for the theme', 'sis'); ?>" />
		<p> <?php _e( 'Copy and paste the code below into your Wordpress theme function file if you wanted to save them and deactivate the plugin.', 'sis'); ?> </p>
		<code></code>
	<?php
	}
	
	/**
	 * Display the Table of sizes and post types for regenerating
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public function thumbnailRegenerate() {
		if( is_file( SIS_DIR.'/templates/options-media.php' ) ) {
			include( SIS_DIR.'/templates/options-media.php' );
		} else {
			esc_html_e( 'Admin option-media template missing' );
		}
	}
		
	/**
	 * Add a size by Ajax
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_AddSize() {
		
		// Get the nonce
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce']: '' ;
		
		// Get old options
		$sizes = (array)get_option( SIS_OPTION, array() );
		
		// Check entries
		$name = isset( $_POST['name'] ) ? sanitize_title( $_POST['name'] ): '' ;
		$height = !isset( $_POST['height'] )? 0 : absint( $_POST['height'] );
		$width =  !isset( $_POST['width'] )? 0 : absint( $_POST['width'] );
		$crop = isset( $_POST['crop'] ) &&  $_POST['crop'] == 'false' ? false : true;
		$show = isset( $_POST['show'] ) &&  $_POST['show'] == 'false' ? false : true;
		$cn = isset( $_POST['customName'] ) && !empty( $_POST['customName'] ) ? sanitize_text_field( $_POST['customName'] ): $name ;
		
		// Check the nonce
		if( !wp_verify_nonce( $nonce , 'add_size' ) ) {
			die(0);
		}
		
		// If no name given do not save
		if( empty( $name ) ) {
			die(0);
		}

		// Make values
		$values = array( 'custom' => 1, 'w' => $width , 'h' => $height, 'c' => $crop, 's' => $show, 'n' => $cn );

		// If the size have not changed return 2
		if( isset( $sizes[$name] ) && $sizes[$name] === $values ) {
			die(2);
		}
		
		// Put the new values
		$sizes[$name] = $values;
		
		// display update result
		echo (int)update_option( 'custom_image_sizes', $sizes );
		die();
	}
	
	/**
	 * Remove a size by Ajax
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_RemoveSize() {
		
		// Get old options
		$sizes = (array)get_option( SIS_OPTION, array() );
		
		// Get the nonce and name
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce']: '' ;
		$name = isset( $_POST['name'] ) ? sanitize_title( $_POST['name'] ): '' ;
		
		// Check the nonce
		if( !wp_verify_nonce( $nonce , 'delete_'.$name ) ) {
			die(0);
		}
		
		// Remove the size
		unset( $sizes[sanitize_title( $name )] );
		unset( $sizes[0] );
		
		// Display the results
		echo (int)update_option( SIS_OPTION, $sizes );
		die();
	}
	
	/**
	 * Display the add_image_size for the registered sizes
	 * 
	 * @access public
	 * @return void
	 */
	public static function a_GetSizes() {
		global $_wp_additional_image_sizes;

		foreach ( get_intermediate_image_sizes() as $s ) {
			// Don't make the original sizes
			if( in_array( $s, self::$original ) ) {
				continue;
			}
			
			// Set width
			$width = isset( $_wp_additional_image_sizes[$s]['width'] ) ? intval( $_wp_additional_image_sizes[$s]['width'] ) : get_option( "{$s}_size_w" ) ;
			
			// Set height
			$height = isset( $_wp_additional_image_sizes[$s]['height'] ) ? intval( $_wp_additional_image_sizes[$s]['height'] ) : get_option( "{$s}_size_h" ) ;
			
			//Set crop
			$crop = isset( $_wp_additional_image_sizes[$s]['crop'] ) ? intval( $_wp_additional_image_sizes[$s]['crop'] ) : get_option( "{$s}_crop" ) ;
			
			$crop = ( $crop == 0 )? 'false' : 'true' ;
			?>
				add_image_size( '<?php echo $s; ?>', '<?php echo $width; ?>', '<?php echo $height; ?>', <?php echo $crop ?> );<br />
			<?php 
		}
		
		die();
	}
	/**
	 * 
	 * Get the media list to regenerate
	 * 
	 * @param : void
	 * @return oid
	 */
	public static function a_GetList() {
		global $wpdb;
		// Basic vars
		$res = array();
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce']: '' ;
		
		// Check the nonce
		if( !wp_verify_nonce( $nonce , 'getList' ) ) {
			self::displayJson();
		}
		
		if ( isset( $_POST['post_types'] ) && !empty( $_POST['post_types'] ) ) {
				
			foreach( $_POST['post_types'] as $key => $type ) {
				if( !post_type_exists( $type ) ) {
					unset( $_POST['post_types'][$key] );
				}
			}
			
			if( empty( $_POST['post_types'][$key]) ) {
				self::displayJson();
			}
			
			// Get image medias
			$whichmimetype = wp_post_mime_type_where( 'image', $wpdb->posts );
			
			// Get all parent from post type
			$attachments = $wpdb->get_results( "SELECT *
				FROM $wpdb->posts 
				WHERE 1 = 1
				AND post_type = 'attachment'
				$whichmimetype
				AND post_parent IN (
					SELECT DISTINCT ID 
					FROM $wpdb->posts 
					WHERE post_type IN ('".implode( "', '", $_POST['post_types'] )."')
				)" );
				
		} else {
			$attachments =& get_children( array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'numberposts' => -1,
				'post_status' => null,
				'post_parent' => null, // any parent
				'output' => 'object',
			) );
		}
		
		// Get the attachments
		foreach ( $attachments as $attachment ) {
			$res[] = array( 'id' => $attachment->ID, 'title' => $attachment->post_title );
		}
		// Return the Id's and Title of medias
		self::displayJson( $res );
	}
	
	/**
	 * Rebuild the image
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_ThumbnailRebuild() {
		global $wpdb;
		
		// Get the nonce
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce']: '' ;
		
		// Time a the begining
		$start_time = microtime( true );
		
		// Get the thumbnails
		$thumbnails = isset( $_POST['thumbnails'] )? $_POST['thumbnails'] : NULL;
			
		// Check the nonce
		if( !wp_verify_nonce( $nonce , 'regen' ) ) {
			self::displayJson( array( 'error' => _e( 'Trying to cheat ?', 'sis' ) ) );
		}
		
		// Get the id
		$id = isset( $_POST["id"] ) ? $_POST["id"] : 0 ;
		
		// Check Id
		if( (int)$id <= 0 ) {
			self::displayJson( 
				array( 
					'time' => round( microtime( true ) - $start_time, 4 ), 
					'error' => __( 'No id given in POST datas.', 'sis' ) 
				) 
			);
		}
		
		// Get the path
		$fullsizepath = get_attached_file( $id );

		// Regen the attachment
		if ( false !== $fullsizepath && @file_exists( $fullsizepath ) ) {
			set_time_limit( 60 );
			if( wp_update_attachment_metadata( $id, self::wp_generate_attachment_metadata_custom( $id, $fullsizepath, $thumbnails ) ) == false ) {
				self::displayJson( 
					array( 
						'src' => wp_get_attachment_thumb_url( $id ), 
						'time' => round( microtime( true ) - $start_time, 4 ), 
						'message' => sprintf( __( 'This file already exists in this size and have not been regenerated :<br/><a target="_blank" href="%1$s" >%2$s</a>', 'sis'), get_edit_post_link( $id ), get_the_title( $id ) ) 
					) 
				);
			}
		} else {
			self::displayJson(
				array( 
					'src' => wp_get_attachment_thumb_url( $id ), 
					'time' => round( microtime( true ) - $start_time, 4 ), 
					'error' => sprintf( __( 'This file does not exists and have not been regenerated :<br/><a target="_blank" href="%1$s" >%2$s</a>', 'sis'), get_edit_post_link( $id ), get_the_title( $id ) ) 
				)
			);
		}
		// Display the attachment url for feedback 
		self::displayJson( 
			array( 
				'time' => round( microtime( true ) - $start_time, 4 ) , 
				'src' => wp_get_attachment_thumb_url( $id ), 
				'title' => get_the_title( $id ) 
			) 
		);
	}

	/**
	 * Generate post thumbnail attachment meta data.
	 *
	 * @since 2.1.0
	 *
	 * @param int $attachment_id Attachment Id to process.
	 * @param string $file Filepath of the Attached image.
	 * @return mixed Metadata for attachment.
	 */
	public static function wp_generate_attachment_metadata_custom( $attachment_id, $file, $thumbnails = NULL ) {
		$attachment = get_post( $attachment_id );
		
		$meta_datas = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

		$metadata = array();
		if ( preg_match('!^image/!', get_post_mime_type( $attachment )) && file_is_displayable_image($file) ) {
			$imagesize = getimagesize( $file );
			$metadata['width'] = $imagesize[0];
			$metadata['height'] = $imagesize[1];
			list($uwidth, $uheight) = wp_constrain_dimensions($metadata['width'], $metadata['height'], 128, 96);
			$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";

			// Make the file path relative to the upload dir
			$metadata['file'] = _wp_relative_upload_path($file);

			// make thumbnails and other intermediate sizes
			global $_wp_additional_image_sizes;

			foreach ( get_intermediate_image_sizes() as $s ) {
				$sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => FALSE );
				if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
					$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
				else
					$sizes[$s]['width'] = get_option( "{$s}_size_w" ); // For default sizes set in options
				if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
					$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
				else
					$sizes[$s]['height'] = get_option( "{$s}_size_h" ); // For default sizes set in options
				if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
					$sizes[$s]['crop'] = intval( $_wp_additional_image_sizes[$s]['crop'] ); // For theme-added sizes
				else
					$sizes[$s]['crop'] = get_option( "{$s}_crop" ); // For default sizes set in options
			}

			$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );

			// Only if not all sizes
			if( isset( $thumbnails ) &&  is_array( $thumbnails ) && isset( $meta_datas['sizes'] ) && !empty( $meta_datas['sizes'] ) ) {
				// Fill the array with the other sizes not have to be done
				foreach( $meta_datas['sizes'] as $name => $fsize ) {
					$metadata['sizes'][$name] = $fsize;
				}
			}

			foreach ( $sizes as $size => $size_data ) {
				if( isset( $thumbnails ) )
					if( !in_array( $size, $thumbnails ) ) {
						continue;
					}

				$resized = image_make_intermediate_size( $file, $size_data['width'], $size_data['height'], $size_data['crop'] );
				
				if( isset( $meta_datas['size'][$size] ) ) {
					// Remove the size from the orignal sizes for after work
					unset( $meta_datas['size'][$size] );
				}
				
				if ( $resized ) {
					$metadata['sizes'][$size] = $resized;
				}
			}
			
			// fetch additional metadata from exif/iptc
			$image_meta = wp_read_image_metadata( $file );
			if ( $image_meta ) {
				$metadata['image_meta'] = $image_meta;
			}
		}

		return apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id );
	}
	
	/**
	 * Add the custom sizes to the image sizes in article edition
	 * 
	 * @access public
 	 * @param array $form_fields
	 * @param object $post
	 * @return void
	 * @author Nicolas Juen
	 * @author Additional Image Sizes (zui)
	 */
	public static function sizesInForm( $form_fields, $post ) {
		// Protect from being view in Media editor where there are no sizes
		if ( isset( $form_fields['image-size'] ) ) {
			$out = NULL;
			$size_names = array();
			$sizes_custom = get_option( SIS_OPTION, array() );
			
			if ( is_array( $sizes_custom ) ) {
				foreach( $sizes_custom as $key => $value ) {
					if( isset( $value['s'] ) && $value['s'] == 1 ) {
						$size_names[$key] = self::_getThumbnailName( $key );;
					}
				}
			}
			foreach ( $size_names as $size => $label ) {
				$downsize = image_downsize( $post->ID, $size );
		
				// is this size selectable?
				$enabled = ( $downsize[3] || 'full' == $size );
				$css_id = "image-size-{$size}-{$post->ID}";

				// We must do a clumsy search of the existing html to determine is something has been checked yet
				if ( FALSE === strpos( 'checked="checked"', $form_fields['image-size']['html'] ) ) {
					if ( empty($check) )
						$check = get_user_setting( 'imgsize' ); // See if they checked a custom size last time

					$checked = '';

					// if this size is the default but that's not available, don't select it
					if ( $size == $check || str_replace( " ", "", $size ) == $check ) {
						if ( $enabled )
							$checked = " checked='checked'";
						else
							$check = '';
					} elseif ( !$check && $enabled && 'thumbnail' != $size ) {
						// if $check is not enabled, default to the first available size that's bigger than a thumbnail
						$check = $size;
						$checked = " checked='checked'";
					}
				}
				$html = "<div class='image-size-item' style='min-height: 50px; margin-top: 18px;'><input type='radio' " . disabled( $enabled, false, false ) . "name='attachments[$post->ID][image-size]' id='{$css_id}' value='{$size}'$checked />";

				$html .= "<label for='{$css_id}'>$label</label>";
				// only show the dimensions if that choice is available
				if ( $enabled )
					$html .= " <label for='{$css_id}' class='help'>" . sprintf( "(%d&nbsp;&times;&nbsp;%d)", $downsize[1], $downsize[2] ). "</label>";

				$html .= '</div>';

				$out .= $html;
			}
				$form_fields['image-size']['html'] .= $out;
		} // End protect from Media editor
		
		return $form_fields;
	}

	/**
	 * Add the thumbnail name in the post insertion, based on new WP filter
	 * 
	 * @access public
 	 * @param array $sizes
	 * @return array
	 * @since 2.3
	 * @author Nicolas Juen
	 * @author radeno based on this post : http://www.wpmayor.com/wordpress-hacks/how-to-add-custom-image-sizes-to-wordpress-uploader/
	 */
	public static function AddThumbnailName($sizes) {
		// Get options
		$sizes_custom = get_option( SIS_OPTION, array() );
		// init size array
		$addsizes = array();
		
		// check there is custom sizes
		if ( is_array( $sizes_custom ) && !empty( $sizes_custom ) ) {
			foreach( $sizes_custom as $key => $value ) {
				// If we show this size in the admin
				if( isset( $value['s'] ) && $value['s'] == 1 ) {
					$addsizes[$key] = self::_getThumbnailName( $key );
				}
			}
		}
		
		// Merge the two array
		$newsizes = array_merge($sizes, $addsizes);
		
		// Add new size
		return $newsizes;
	}
	
	/**
	 * Get a thumbnail name from its slug
	 * 
	 * @access private
 	 * @param string $thumbnailSlug : the slug of the thumbnail
	 * @return array
	 * @since 2.3
	 * @author Nicolas Juen
	 */
	private static function _getThumbnailName( $thumbnailSlug = '' ) {
		
		// get the options
		$sizes_custom = get_option( SIS_OPTION );
		
		// If the size exists
		if( isset( $sizes_custom[$thumbnailSlug] ) ) {
			// If the name exists return it, slug by default
			if( isset( $sizes_custom[$thumbnailSlug]['n'] ) && !empty( $sizes_custom[$thumbnailSlug]['n'] ) ) {
				return $sizes_custom[$thumbnailSlug]['n'];
			} else {
				return $thumbnailSlug;
			}
		}
		
		// return slug if not found
		return $thumbnailSlug;
	}
	
	/**
	 * Get a thumbnail name from its slug
	 * 
	 * @access public
 	 * @param array $fields : the fields of the media
	 * @param object $post : the post object
	 * @return array
	 * @since 2.3.1
	 * @author Nicolas Juen
	 */
	public static function addFieldRegenerate( $fields, $post ) {
		// Check this is an image
		if( strpos( $post->post_mime_type, 'image' ) === false ) {
			return $fields;
		}
		
		$fields['sis-regenerate'] = array(
			'label'	=> __( 'Regenerate Thumbnails', 'sis' ),
			'input'	=> 'html',
			'html'	=> '
			<input type="button" data-id="'.$post->ID.'" class="button title sis-regenerate-one" value="'.__( 'Regenerate Thumbnails', 'sis' ).'" />
			<span class="title"><em></em></span>
			<input type="hidden" class="regen" value="'.wp_create_nonce( 'regen' ).'" />',
			'show_in_edit' => true,
			'show_in_modal' => false,
		);
		return $fields;
	}
	
	/**
	 * Display a json encoded element with right headers
	 * 
	 * @param $data(optional) : the element to display ( if needed )
	 * @return void
	 * @author Nicolas Juen
	 */
	private static function displayJson( $data = array() ) {
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		echo json_encode( $data );
		die();
	}
}