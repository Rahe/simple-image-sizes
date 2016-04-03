<?php

Class SIS_Admin_Media {

	// Original sizes
	public static $original = array( 'thumbnail', 'medium', 'large' );

	public function __construct() {
		// Init
		add_action( 'admin_menu', array( __CLASS__, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 11 );

		// Add ajax action
		// Option page
		add_action( 'wp_ajax_' . 'sis_get_list', array( __CLASS__, 'a_get_list' ) );
		add_action( 'wp_ajax_' . 'sis_rebuild_images', array( __CLASS__, 'a_thumbnails_rebuild' ) );
		add_action( 'wp_ajax_' . 'sis_get_sizes', array( __CLASS__, 'a_get_sizes' ) );
		add_action( 'wp_ajax_' . 'sis_add_size', array( __CLASS__, 'a_add_size' ) );
		add_action( 'wp_ajax_' . 'sis_remove_size', array( __CLASS__, 'a_remove_size' ) );

		// Add link in plugins list
		add_filter( 'plugin_action_links', array( __CLASS__, 'add_settings_link' ), 10, 2 );
	}

	/**
	 * Register javascripts and css.
	 *
	 * @access public
	 *
	 * @param string $hook_suffix
	 *
	 * @author Nicolas Juen
	 */
	public static function enqueue_assets( $hook_suffix = '' ) {
		if ( ! isset( $hook_suffix ) || empty( $hook_suffix ) ) {
			return;
		}

		if ( 'options-media.php' == $hook_suffix ) {
			// Add javascript
			wp_enqueue_script( 'sis_js' );

			// Add CSS
			wp_enqueue_style( 'sis_css' );

			// Add underscore template
			add_action( 'admin_footer', array( 'SIS_Admin_Main', 'add_template' ) );
		}
	}


	/**
	 * Add a link to the setting option page
	 *
	 * @access public
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return string
	 * @author Nicolas Juen
	 */
	public static function add_settings_link( $links, $file ) {

		if ( 'simple-image-sizes/simple_image_sizes.php' !== $file ) {
			return $links;
		}

		$settings_link = '<a href="' . admin_url( 'options-media.php' ) . '"> ' . __( 'Settings', 'simple-image-sizes' ) . ' </a>';
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
	public static function init() {
		// Check if admin
		if ( ! is_admin() ) {
			return;
		}

		// Get the image sizes
		global $_wp_additional_image_sizes;

		// Get the sizes and add the settings
		foreach ( get_intermediate_image_sizes() as $s ) {
			// Don't make the original sizes or numeric sizes that appear
			if ( in_array( $s, self::$original ) || is_integer( $s ) ) {
				continue;
			}

			// Set width
			$width = isset( $_wp_additional_image_sizes[ $s ]['width'] ) ? intval( $_wp_additional_image_sizes[ $s ]['width'] ) : get_option( "{$s}_size_w" );

			// Set height
			$height = isset( $_wp_additional_image_sizes[ $s ]['height'] ) ? intval( $_wp_additional_image_sizes[ $s ]['height'] ) : get_option( "{$s}_size_h" );

			//Set crop
			$crop = isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ? intval( $_wp_additional_image_sizes[ $s ]['crop'] ) : get_option( "{$s}_crop" );

			// Add the setting field for this size
			add_settings_field( 'image_size_' . $s, sprintf( __( '%s size', 'simple-image-sizes' ), $s ), array(
				__CLASS__,
				'image_sizes'
			), 'media', 'default', array( 'name' => $s, 'width' => $width, 'height' => $height, 'c' => $crop ) );
		}

		// Register the setting for media option page
		register_setting( 'media', SIS_OPTION );

		// Add the button
		add_settings_field( 'add_size_button', __( 'Add a new size', 'simple-image-sizes' ), array(
			__CLASS__,
			'addSizeButton'
		), 'media' );

		// Add php button
		add_settings_field( 'get_php_button', __( 'Get php for theme', 'simple-image-sizes' ), array(
			__CLASS__,
			'getPhpButton'
		), 'media' );

		// Add section for the thumbnail regeneration
		add_settings_section( 'thumbnail_regenerate', __( 'Thumbnail regeneration', 'simple-image-sizes' ), array(
			__CLASS__,
			'thumbnailRegenerate'
		), 'media' );
	}

	/**
	 * Display the row of the image size
	 *
	 * @access public
	 *
	 * @param mixed $args
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function image_sizes( $args ) {

		if ( is_integer( $args['name'] ) ) {
			return;
		}

		// Get the options
		$sizes = (array) get_option( SIS_OPTION, array() );

		// Get the vars
		$height = isset( $sizes[ $args['name'] ]['h'] ) ? $sizes[ $args['name'] ]['h'] : $args['height'];
		$width  = isset( $sizes[ $args['name'] ]['w'] ) ? $sizes[ $args['name'] ]['w'] : $args['width'];
		$crop   = isset( $sizes[ $args['name'] ]['c'] ) && ! empty( $sizes[ $args['name'] ]['c'] ) ? $sizes[ $args['name'] ]['c'] : $args['c'];
		$show   = isset( $sizes[ $args['name'] ]['s'] ) && ! empty( $sizes[ $args['name'] ]['s'] ) ? '1' : '0';
		$custom = isset( $sizes[ $args['name'] ]['custom'] ) && ! empty( $sizes[ $args['name'] ]['custom'] ) ? '1' : '0';
		$name   = isset( $sizes[ $args['name'] ]['n'] ) && ! empty( $sizes[ $args['name'] ]['n'] ) ? esc_html( $sizes[ $args['name'] ]['n'] ) : esc_html( $args['name'] );
		?>
		<input type="hidden" value="<?php echo esc_attr( $args['name'] ); ?>" name="image_name"/>
		<?php if ( $custom ): ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][custom]' ); ?>" type="hidden"
			       id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][custom]' ); ?>" value="1"/>
		<?php else: ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][theme]' ); ?>" type="hidden"
			       id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][theme]' ); ?>" value="1"/>
		<?php endif; ?>
		<label class="sis-label" for="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][w]' ); ?>">
			<?php _e( 'Maximum width', 'simple-image-sizes' ); ?>
			<input name="<?php esc_attr_e( 'custom_image_sizes[' . $args['name'] . '][w]' ); ?>" class='w small-text'
			       type="number" step='1' min='0'
			       id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][w]' ); ?>"
			       base_w='<?php echo esc_attr( $width ); ?>' value="<?php echo esc_attr( $width ); ?>"/>
		</label>
		<label class="sis-label" for="<?php esc_attr_e( 'custom_image_sizes[' . $args['name'] . '][h]' ); ?>">
			<?php _e( 'Maximum height', 'simple-image-sizes' ); ?>
			<input name="<?php esc_attr_e( 'custom_image_sizes[' . $args['name'] . '][h]' ); ?>" class='h small-text'
			       type="number" step='1' min='0'
			       id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][h]' ); ?>"
			       base_h='<?php echo esc_attr( $height ); ?>' value="<?php echo esc_attr( $height ); ?>"/>
		</label>
		<label class="sis-label" for="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][n]' ); ?>">
			<?php _e( 'Public name', 'simple-image-sizes' ); ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][n]' ); ?>" class='n'
			       type="text" id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][n]' ); ?>"
			       base_n='<?php echo $name; ?>' value="<?php echo $name ?>"/>
		</label>
		<span class="size_options">
			<label class="c"
			       for="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][c]' ); ?>"><?php _e( 'Cropping', 'simple-image-sizes' ); ?></label>
			<select id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][c]' ); ?>" class="c crop"
			        base_c='<?php echo esc_attr( $crop ); ?>'
			        name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][c]' ); ?>">

				<?php foreach ( SIS_Admin_Main::get_available_crop() as $crop_position => $label ): ?>
					<option <?php selected( $crop_position, $crop ); ?>
						value="<?php echo esc_attr( $crop_position ) ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			
			<input type='checkbox'
			       id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][s]' ); ?>" <?php checked( $show, 1 ) ?>
			       class="s show" base_s='<?php echo esc_attr( $show ); ?>'
			       name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][s]' ); ?>" value="1"/>
			<label class="s"
			       for="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][s]' ); ?>"><?php _e( 'Show in post insertion ?', 'simple-image-sizes' ); ?></label>
		</span>
		<span class="delete_size  button-secondary"><?php _e( 'Delete', 'simple-image-sizes' ); ?></span>
		<span class="add_size validate_size button-primary"><?php _e( 'Update', 'simple-image-sizes' ); ?></span>

		<input type="hidden" class="deleteSize button-primary"
		       value='<?php echo wp_create_nonce( 'delete_' . $args['name'] ); ?>'/>
	<?php }

	/**
	 * Add the button to add a size
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function addSizeButton() { ?>
		<input type="button" class="button-secondary action" id="add_size"
		       value="<?php esc_attr_e( 'Add a new size of thumbnail', 'simple-image-sizes' ); ?>"/>
	<?php
	}

	/**
	 * Add the button to get the php for th sizes
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function getPhpButton() { ?>
		<input type="button" class="button-secondary action" id="get_php"
		       value="<?php esc_attr_e( 'Get the PHP for the theme', 'simple-image-sizes' ); ?>"/>
		<p> <?php _e( 'Copy and paste the code below into your Wordpress theme function file if you wanted to save them and deactivate the plugin.', 'simple-image-sizes' ); ?> </p>
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
	public static function thumbnailRegenerate() {
		if ( is_file( SIS_DIR . '/templates/options-media.php' ) ) {
			include( SIS_DIR . '/templates/options-media.php' );
		} else {
			esc_html_e( 'Admin option-media template missing', 'simple-image-sizes' );
		}
	}

	/**
	 * Add a size by Ajax
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_add_size() {

		// Get the nonce
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

		// Get old options
		$sizes              = (array) get_option( SIS_OPTION, array() );
		$croppings          = SIS_Admin_Main::get_available_crop();
		$croppings[ true ]  = '';
		$croppings[ false ] = '';

		// Check entries
		$name   = isset( $_POST['name'] ) ? sanitize_title( $_POST['name'] ) : '';
		$height = ! isset( $_POST['height'] ) ? 0 : absint( $_POST['height'] );
		$width  = ! isset( $_POST['width'] ) ? 0 : absint( $_POST['width'] );
		$crop   = isset( $_POST['crop'] ) && isset( $croppings[ $_POST['crop'] ] ) ? $_POST['crop'] : false;
		$show   = isset( $_POST['show'] ) && $_POST['show'] == 'false' ? false : true;
		$cn     = isset( $_POST['customName'] ) && ! empty( $_POST['customName'] ) ? sanitize_text_field( $_POST['customName'] ) : $name;

		// Check the nonce
		if ( ! wp_verify_nonce( $nonce, 'add_size' ) ) {
			die( 0 );
		}

		// If no name given do not save
		if ( empty( $name ) ) {
			die( 0 );
		}

		// Make values
		$values = array( 'custom' => 1, 'w' => $width, 'h' => $height, 'c' => $crop, 's' => $show, 'n' => $cn );

		// If the size have not changed return 2
		if ( isset( $sizes[ $name ] ) && $sizes[ $name ] === $values ) {
			die( 2 );
		}

		// Put the new values
		$sizes[ $name ] = $values;

		// display update result
		echo (int) update_option( 'custom_image_sizes', $sizes );
		die();
	}

	/**
	 * Remove a size by Ajax
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_remove_size() {

		// Get old options
		$sizes = (array) get_option( SIS_OPTION, array() );

		// Get the nonce and name
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$name  = isset( $_POST['name'] ) ? sanitize_title( $_POST['name'] ) : '';

		// Check the nonce
		if ( ! wp_verify_nonce( $nonce, 'delete_' . $name ) ) {
			die( 0 );
		}

		// Remove the size
		unset( $sizes[ sanitize_title( $name ) ] );
		unset( $sizes[0] );

		// Display the results
		echo (int) update_option( SIS_OPTION, $sizes );
		die();
	}

	/**
	 * Display the add_image_size for the registered sizes
	 *
	 * @access public
	 * @return void
	 */
	public static function a_get_sizes() {
		global $_wp_additional_image_sizes, $wp_version;

		foreach ( get_intermediate_image_sizes() as $s ) {
			// Don't make the original sizes
			if ( in_array( $s, self::$original ) ) {
				continue;
			}

			// Set width
			$width = isset( $_wp_additional_image_sizes[ $s ]['width'] ) ? intval( $_wp_additional_image_sizes[ $s ]['width'] ) : get_option( "{$s}_size_w" );

			// Set height
			$height = isset( $_wp_additional_image_sizes[ $s ]['height'] ) ? intval( $_wp_additional_image_sizes[ $s ]['height'] ) : get_option( "{$s}_size_h" );

			//Set crop
			$crop = isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ? $_wp_additional_image_sizes[ $s ]['crop'] : get_option( "{$s}_crop" );

			if ( is_bool( $crop ) || is_numeric( $crop ) || version_compare( $wp_version, '3.9', '<' ) ) {
				$crop = ( absint( $crop ) == 0 ) ? 'false' : 'true';
			} else {
				if ( ! Sis_Admin_Main::is_crop_position( implode( '_', $crop ) ) ) {
					$crop = "false";
				} else {
					$crop = 'array( "' . $crop[0] . '", "' . $crop[1] . '")';
				}
			}
			?>
			add_image_size( '<?php echo $s; ?>', '<?php echo $width; ?>', '<?php echo $height; ?>', <?php echo $crop; ?> );
			<br/>
		<?php
		}

		die();
	}

	/**
	 *
	 * Get the media list to regenerate
	 *
	 * @param : void
	 *
	 * @return void
	 */
	public static function a_get_list() {
		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		// Basic vars
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

		// Check the nonce
		if ( ! wp_verify_nonce( $nonce, 'getList' ) ) {
			wp_send_json();
		}

		if ( isset( $_POST['post_types'] ) && ! empty( $_POST['post_types'] ) ) {

			foreach ( $_POST['post_types'] as $key => $type ) {
				if ( ! post_type_exists( $type ) ) {
					unset( $_POST['post_types'][ $key ] );
				}
			}

			if ( empty( $_POST['post_types'][ $key ] ) ) {
				wp_send_json();
			}

			// Get image medias
			$whichmimetype = wp_post_mime_type_where( 'image', $wpdb->posts );

			// Get all parent from post type
			$attachments = $wpdb->get_var( "SELECT COUNT( ID )
				FROM $wpdb->posts 
				WHERE 1 = 1
				AND post_type = 'attachment'
				$whichmimetype
				AND post_parent IN (
					SELECT DISTINCT ID 
					FROM $wpdb->posts 
					WHERE post_type IN ('" . implode( "', '", $_POST['post_types'] ) . "')
				)" );
			// Return the Id's and Title of medias
			wp_send_json( array( 'total' => $attachments ) );

		} else {
			$attachments = get_children( array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'numberposts'    => - 1,
				'post_status'    => null,
				'post_parent'    => null, // any parent
				'output'         => 'ids',
			) );
			// Return the Id's and Title of medias
			wp_send_json( array( 'total' => count( $attachments ) ) );
		}

	}

	/**
	 * Regenerate the thumbnails ajax action
	 *
	 * @return array
	 *
	 * @param void
	 *
	 * @author Nicolas Juen
	 */
	public static function a_thumbnails_rebuild() {
		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		// Get the nonce
		$nonce      = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$offset     = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$post_types = isset( $_POST['post_types'] ) ? $_POST['post_types'] : 'any';
		$thumbnails = isset( $_POST['thumbnails'] ) ? $_POST['thumbnails'] : null;

		// Check the nonce
		if ( ! wp_verify_nonce( $nonce, 'regen' ) ) {
			wp_send_json( array( 'error' => __( 'Trying to cheat ?', 'simple-image-sizes' ) ) );
		}

		if ( 'any' !== $post_types ) {

			foreach ( $_POST['post_types'] as $key => $type ) {
				if ( ! post_type_exists( $type ) ) {
					unset( $_POST['post_types'][ $key ] );
				}
			}

			if ( empty( $_POST['post_types'] ) ) {
				wp_send_json();
			}

			// Get image medias
			$whichmimetype = wp_post_mime_type_where( 'image', $wpdb->posts );

			// Get all parent from post type
			$attachment = $wpdb->get_var( $wpdb->prepare( "SELECT ID
				FROM $wpdb->posts 
				WHERE 1 = 1
				AND post_type = 'attachment'
				$whichmimetype
				AND post_parent IN (
					SELECT DISTINCT ID 
					FROM $wpdb->posts 
					WHERE post_type IN ('" . implode( "', '", $_POST['post_types'] ) . "')
				)
				LIMIT %d,1 
			", $offset ) );

		} else {
			$attachment = get_posts( array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'numberposts'    => 1,
				'post_status'    => 'any',
				'output'         => 'object',
				'offset'         => $offset,
			) );

			$attachment = ! empty( $attachment ) ? $attachment[0]->ID : 0;
		}

		if ( empty( $attachment ) ) {
			return array(
				'message' => __( 'Regeneration ended', 'simple-image-sizes' )
			);
		}
		wp_send_json( SIS_Admin_Main::thumbnail_rebuild( $attachment, $thumbnails ) );
	}
}
