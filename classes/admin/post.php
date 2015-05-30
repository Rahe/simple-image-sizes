<?php

Class SIS_Admin_Post {
	public function __construct() {
		// Add image sizes in the form, check if 3.3 is installed or not
		if ( ! function_exists( 'is_main_query' ) ) {
			add_filter( 'attachment_fields_to_edit', array(
				__CLASS__,
				'sizes_in_form'
			), 11, 2 ); // Add our sizes to media forms
		} else {
			add_filter( 'image_size_names_choose', array( __CLASS__, 'add_thumbnail_name' ) );
		}

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 11 );

		// Rebuilt the image
		add_action( 'wp_ajax_' . 'sis_rebuild_image', array( __CLASS__, 'a_thumbnail_rebuild' ) );

		// Add action in media row quick actions
		add_filter( 'media_row_actions', array( __CLASS__, 'add_actions_list' ), 10, 2 );

		// Add filter for the Media single
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'add_field_regenerate' ), 9, 2 );
	}

	/**
	 * Register javascripts and css.
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function enqueue_assets( $hook_suffix = '' ) {
		if ( ! isset( $hook_suffix ) || empty( $hook_suffix ) ) {
			return false;
		}

		if ( 'upload.php' == $hook_suffix || ( 'post.php' == $hook_suffix && isset( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) ) {
			// Add javascript
			wp_enqueue_script( 'sis_js_attachments' );

			// Add underscore template
			add_action( 'admin_footer', array( 'SIS_Admin_Main', 'add_template' ) );
		}
	}

	/**
	 * Rebuild the image
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_thumbnail_rebuild() {
		// Get the nonce
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

		// Time a the begining
		timer_start();

		// Get the thumbnails
		$thumbnails = isset( $_POST['thumbnails'] ) ? $_POST['thumbnails'] : null;

		// Check the nonce
		if ( ! wp_verify_nonce( $nonce, 'regen' ) ) {
			SIS_Admin_Main::displayJson( array( 'error' => _e( 'Trying to cheat ?', 'simple-image-sizes' ) ) );
		}

		// Get the id
		$id = isset( $_POST['id'] ) ? $_POST['id'] : 0;
		SIS_Admin_Main::displayJson( SIS_Admin_Main::thumbnail_rebuild( $id, $thumbnails ) );
	}

	/**
	 * Add the custom sizes to the image sizes in article edition
	 *
	 * @access public
	 *
	 * @param array $form_fields
	 * @param object $post
	 *
	 * @return string
	 * @author Nicolas Juen
	 * @author Additional Image Sizes (zui)
	 */
	public static function sizes_in_form( $form_fields, $post ) {
		// Protect from being view in Media editor where there are no sizes
		if ( ! isset( $form_fields['image-size'] ) ) {
			return $form_fields;
		}

		$out          = null;
		$size_names   = array();
		$sizes_custom = get_option( SIS_OPTION, array() );

		if ( is_array( $sizes_custom ) ) {
			foreach ( $sizes_custom as $key => $value ) {
				if ( isset( $value['s'] ) && $value['s'] == 1 ) {
					$size_names[ $key ] = self::_get_thumbnail_name( $key );;
				}
			}
		}
		foreach ( $size_names as $size => $label ) {
			$downsize = image_downsize( $post->ID, $size );

			// is this size selectable?
			$enabled = ( $downsize[3] || 'full' == $size );
			$css_id  = "image-size-{$size}-{$post->ID}";

			// We must do a clumsy search of the existing html to determine is something has been checked yet
			if ( false === strpos( 'checked="checked"', $form_fields['image-size']['html'] ) ) {
				if ( empty( $check ) ) {
					$check = get_user_setting( 'imgsize' );
				} // See if they checked a custom size last time

				$checked = '';

				// if this size is the default but that's not available, don't select it
				if ( $size == $check || str_replace( " ", "", $size ) == $check ) {
					if ( $enabled ) {
						$checked = " checked='checked'";
					} else {
						$check = '';
					}
				} elseif ( ! $check && $enabled && 'thumbnail' != $size ) {
					// if $check is not enabled, default to the first available size that's bigger than a thumbnail
					$check   = $size;
					$checked = " checked='checked'";
				}
			}
			$html = "<div class='image-size-item' style='min-height: 50px; margin-top: 18px;'><input type='radio' " . disabled( $enabled, false, false ) . "name='attachments[$post->ID][image-size]' id='{$css_id}' value='{$size}'$checked />";

			$html .= "<label for='{$css_id}'>$label</label>";
			// only show the dimensions if that choice is available
			if ( $enabled ) {
				$html .= " <label for='{$css_id}' class='help'>" . sprintf( "(%d&nbsp;&times;&nbsp;%d)", $downsize[1], $downsize[2] ) . "</label>";
			}

			$html .= '</div>';

			$out .= $html;
		}
		$form_fields['image-size']['html'] .= $out;

		return $form_fields;
	}

	/**
	 * Add the thumbnail name in the post insertion, based on new WP filter
	 *
	 * @access public
	 *
	 * @param array $sizes
	 *
	 * @return array
	 * @since 2.3
	 * @author Nicolas Juen
	 * @author radeno based on this post : http://www.wpmayor.com/wordpress-hacks/how-to-add-custom-image-sizes-to-wordpress-uploader/
	 */
	public static function add_thumbnail_name( $sizes ) {
		// Get options
		$sizes_custom = get_option( SIS_OPTION, array() );
		// init size array
		$addsizes = array();

		// check there is custom sizes
		if ( is_array( $sizes_custom ) && ! empty( $sizes_custom ) ) {
			foreach ( $sizes_custom as $key => $value ) {
				// If we show this size in the admin
				if ( isset( $value['s'] ) && 1 == $value['s'] ) {
					$addsizes[ $key ] = self::_get_thumbnail_name( $key );
				}
			}
		}

		// Merge the two array
		$newsizes = array_merge( $sizes, $addsizes );

		// Add new size
		return $newsizes;
	}

	/**
	 * Get a thumbnail name from its slug
	 *
	 * @access private
	 *
	 * @param string $thumbnailSlug : the slug of the thumbnail
	 *
	 * @return array
	 * @since 2.3
	 * @author Nicolas Juen
	 */
	private static function _get_thumbnail_name( $thumbnailSlug = '' ) {

		// get the options
		$sizes_custom = get_option( SIS_OPTION );

		if ( ! isset( $sizes_custom[ $thumbnailSlug ] ) ) {
			// return slug if not found
			return $thumbnailSlug;
		}

		// If the name exists return it, slug by default
		if ( isset( $sizes_custom[ $thumbnailSlug ]['n'] ) && ! empty( $sizes_custom[ $thumbnailSlug ]['n'] ) ) {
			return $sizes_custom[ $thumbnailSlug ]['n'];
		}

		return $thumbnailSlug;
	}


	/**
	 * Add action in media row
	 *
	 * @since 2.2
	 * @access public
	 * @return $actions : array of actions and content to display
	 * @author Nicolas Juen
	 */
	public static function add_actions_list( $actions, $object ) {
		if ( ! wp_attachment_is_image( $object->ID ) ) {
			return $actions;
		}
		// Add action for regeneration
		$actions['sis-regenerate'] = "<a href='#' data-id='" . $object->ID . "' class='sis-regenerate-one'>" . __( 'Regenerate thumbnails', 'simple-image-sizes' ) . "</a>";

		// Return actions
		return $actions;
	}


	/**
	 * Get a thumbnail name from its slug
	 *
	 * @access public
	 *
	 * @param array $fields : the fields of the media
	 * @param object $post : the post object
	 *
	 * @return array
	 * @since 2.3.1
	 * @author Nicolas Juen
	 */
	public static function add_field_regenerate( $fields, $post ) {
		// Check this is an image
		if ( false === strpos( $post->post_mime_type, 'image' ) ) {
			return $fields;
		}

		$fields['sis-regenerate'] = array(
			'label'         => __( 'Regenerate Thumbnails', 'simple-image-sizes' ),
			'input'         => 'html',
			'html'          => '
			<input type="button" data-id="' . $post->ID . '" class="button title sis-regenerate-one" value="' . __( 'Regenerate Thumbnails', 'simple-image-sizes' ) . '" />
			<span class="spinner"></span>
			<span class="title"><em></em></span>
			<input type="hidden" class="regen" value="' . wp_create_nonce( 'regen' ) . '" />',
			'show_in_edit'  => true,
			'show_in_modal' => false,
		);

		return $fields;
	}
}