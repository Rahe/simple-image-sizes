<?php

Class SIS_Admin_Main {

	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register all the assets for the admin
	 *
	 *
	 */
	public static function register_assets() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ? '' : '.min';
		// Add javascript
		wp_register_script( 'underscore', 'https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.0/underscore-min.js', array(), '1.8.0' );
		wp_register_script( 'sis_js', SIS_URL . 'assets/js/dist/app' . $suffix . '.js', array(
			'jquery',
			'jquery-ui-button',
			'jquery-ui-progressbar',
			'underscore',
		), SIS_VERSION );

		// Add javascript translations
		wp_localize_script( 'sis_js', 'sis', self::localize_vars() );

		// Add CSS
		wp_enqueue_style( 'sis_css', SIS_URL . 'assets/css/sis-style' . $suffix . '.css', array(), SIS_VERSION );
	}


	/**
	 * Localize the var for javascript
	 *
	 * @access public
	 * @return array
	 * @author Nicolas Juen
	 */
	public static function localize_vars() {
		return array(
			'reading'            => __( 'Reading attachments...', 'simple-image-sizes' ),
			'maximumWidth'       => __( 'Maximum width', 'simple-image-sizes' ),
			'maximumHeight'      => __( 'Maximum height', 'simple-image-sizes' ),
			'crop'               => __( 'Crop', 'simple-image-sizes' ),
			'tr'                 => __( 'yes', 'simple-image-sizes' ),
			'fl'                 => __( 'no', 'simple-image-sizes' ),
			'show'               => __( 'Show in post insertion ?', 'simple-image-sizes' ),
			'of'                 => __( ' of ', 'simple-image-sizes' ),
			'or'                 => __( ' or ', 'simple-image-sizes' ),
			'beforeEnd'          => __( ' before the end.', 'simple-image-sizes' ),
			'deleteImage'        => __( 'Delete', 'simple-image-sizes' ),
			'noMedia'            => __( 'No media in your site to regenerate !', 'simple-image-sizes' ),
			'regenerating'       => __( 'Regenerating ', 'simple-image-sizes' ),
			'regenerate'         => __( 'Regenerate ', 'simple-image-sizes' ),
			'validate'           => __( 'Validate image size name', 'simple-image-sizes' ),
			'done'               => __( 'Done.', 'simple-image-sizes' ),
			'size'               => __( 'Size', 'simple-image-sizes' ),
			'notOriginal'        => __( 'Don\'t use the basic WordPress thumbnail size name, use the form above to edit them', 'simple-image-sizes' ),
			'alreadyPresent'     => __( 'This size is already registered, edit it instead of recreating it.', 'simple-image-sizes' ),
			'confirmDelete'      => __( 'Do you really want to delete these size ?', 'simple-image-sizes' ),
			'update'             => __( 'Update', 'simple-image-sizes' ),
			'ajaxErrorHandler'   => __( 'Error requesting page', 'simple-image-sizes' ),
			'messageRegenerated' => __( 'images have been regenerated !', 'simple-image-sizes' ),
			'validateButton'     => __( 'Validate', 'simple-image-sizes' ),
			'startedAt'          => __( ' started at', 'simple-image-sizes' ),
			'customName'         => __( 'Public name', 'simple-image-sizes' ),
			'finishedAt'         => __( ' finished at :', 'simple-image-sizes' ),
			'phpError'           => __( 'Error during the php treatment, be sure to not have php errors in your page', 'simple-image-sizes' ),
			'notSaved'           => __( 'All the sizes you have modified are not saved, continue anyway ?', 'simple-image-sizes' ),
			'soloRegenerated'    => __( 'This image has been regenerated in %s seconds', 'simple-image-sizes' ),
			'crop_positions'     => self::get_available_crop(),
			'regen_one'          => wp_create_nonce( 'regen' ),
		);
	}

	/**
	 * Rebuild the given attribute with the given thumbnails
	 *
	 * @param $att_id
	 * @param $thumbnails
	 *
	 * @return array
	 * @author Nicolas Juen
	 */
	public static function thumbnail_rebuild( $att_id, $thumbnails = null ) {
		// Time a the begining
		timer_start();

		// Check Id
		if ( (int) $att_id <= 0 ) {
			return array(
				'time'  => timer_stop( false, 4 ),
				'error' => __( 'No id given in POST datas.', 'simple-image-sizes' ),
			);
		}

		// Get the path
		$fullsizepath = get_attached_file( $att_id );

		// Regen the attachment
		if ( false !== $fullsizepath && file_exists( $fullsizepath ) ) {
			if ( false == wp_update_attachment_metadata( $att_id, self::wp_generate_attachment_metadata_custom( $att_id, $fullsizepath, $thumbnails ) ) ) {
				return array(
					'src'     => wp_get_attachment_thumb_url( $att_id ),
					'time'    => timer_stop( false, 4 ),
					'message' => sprintf( __( 'This file already exists in this size and have not been regenerated :<br/><a target="_blank" href="%1$s" >%2$s</a>', 'simple-image-sizes' ), get_edit_post_link( $att_id ), get_the_title( $att_id ) ),
				);
			}
		} else {
			return array(
				'src'   => wp_get_attachment_thumb_url( $att_id ),
				'time'  => timer_stop( false, 4 ),
				'error' => sprintf( __( 'This file does not exists and have not been regenerated :<br/><a target="_blank" href="%1$s" >%2$s</a>', 'simple-image-sizes' ), get_edit_post_link( $att_id ), get_the_title( $att_id ) ),
			);

		}

		// Display the attachment url for feedback
		return array(
			'time'  => timer_stop( false, 4 ),
			'src'   => wp_get_attachment_thumb_url( $att_id ),
			'title' => get_the_title( $att_id ),
		);
	}

	/**
	 * Include the javascript template
	 *
	 * @param void
	 *
	 * @return bool
	 */
	public static function add_template() {
		global $pagenow;
		if ( 'options-media.php' !== $pagenow ) {
			return false;
		}

		if ( is_file( SIS_DIR . '/templates/admin-js.html' ) ) {
			include( SIS_DIR . '/templates/admin-js.html' );
		}

		return true;
	}

	/**
	 * Get all the available cropping
	 *
	 * @return array
	 *
	 * @param void
	 *
	 * @author Nicolas Juen
	 */
	public static function get_available_crop() {
		global $wp_version;

		// Return the only possible
		if ( version_compare( $wp_version, '3.9', '<' ) ) {
			return array();
		}

		$x = array(
			'left'   => __( 'Left', 'simple-image-sizes' ),
			'center' => __( 'Center', 'simple-image-sizes' ),
			'right'  => __( 'Right', 'simple-image-sizes' ),
		);

		$y = array(
			'top'    => __( 'top', 'simple-image-sizes' ),
			'center' => __( 'center', 'simple-image-sizes' ),
			'bottom' => __( 'bottom', 'simple-image-sizes' ),
		);

		/**
		 * Base crops
		 */
		$crops = array(
			0 => __( 'No','simple-image-sizes' ),
			1 => __( 'Yes','simple-image-sizes' ),
		);
		foreach ( $x as $x_pos => $x_pos_label ) {
			foreach ( $y as $y_pos => $y_pos_label ) {
				$crops[ $x_pos . '_' . $y_pos ] = $x_pos_label . ' ' . $y_pos_label;
			}
		}

		return $crops;
	}

	/**
	 * Check if the crop is available
	 *
	 * @param string $crop_position
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public static function is_crop_position( $crop_position ) {
		$crops = self::get_available_crop();

		return is_bool( $crop_position ) ? $crop_position : isset( $crops[ $crop_position ] );
	}

	/**
	 * Return the crop position label from the slug
	 *
	 *
	 * @param string $crop_position
	 *
	 * @return string
	 * @author Nicolas Juen
	 */
	public static function get_crop_position_label( $crop_position ) {
		if ( ! self::is_crop_position( $crop_position ) ) {
			return '';
		}
		$crops = self::get_available_crop();

		return $crops[ $crop_position ];
	}

	/**
	 * Generate post thumbnail attachment meta data.
	 *
	 * @since 2.1.0
	 *
	 * @param int $attachment_id Attachment Id to process.
	 * @param string $file Filepath of the Attached image.
	 *
	 * @param null|array $thumbnails: thumbnails to regenerate, if null all
	 *
	 * @return mixed Metadata for attachment.
	 */
	public static function wp_generate_attachment_metadata_custom( $attachment_id, $file, $thumbnails = null ) {
		$attachment = get_post( $attachment_id );

		$meta_datas = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

		$metadata = array();
		if ( preg_match( '!^image/!', get_post_mime_type( $attachment ) ) && file_is_displayable_image( $file ) ) {
			$imagesize          = getimagesize( $file );
			$metadata['width']  = $imagesize[0];
			$metadata['height'] = $imagesize[1];
			list( $uwidth, $uheight ) = wp_constrain_dimensions( $metadata['width'], $metadata['height'], 128, 96 );
			$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";

			// Make the file path relative to the upload dir
			$metadata['file'] = _wp_relative_upload_path( $file );

			// make thumbnails and other intermediate sizes
			global $_wp_additional_image_sizes;

			foreach ( get_intermediate_image_sizes() as $s ) {
				$sizes[ $s ] = array( 'width' => '', 'height' => '', 'crop' => false );
				if ( isset( $_wp_additional_image_sizes[ $s ]['width'] ) ) {
					$sizes[ $s ]['width'] = intval( $_wp_additional_image_sizes[ $s ]['width'] );
				} // For theme-added sizes
				else {
					$sizes[ $s ]['width'] = get_option( "{$s}_size_w" );
				} // For default sizes set in options
				if ( isset( $_wp_additional_image_sizes[ $s ]['height'] ) ) {
					$sizes[ $s ]['height'] = intval( $_wp_additional_image_sizes[ $s ]['height'] );
				} // For theme-added sizes
				else {
					$sizes[ $s ]['height'] = get_option( "{$s}_size_h" );
				} // For default sizes set in options
				if ( isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ) {
					$sizes[ $s ]['crop'] = intval( $_wp_additional_image_sizes[ $s ]['crop'] );
				} // For theme-added sizes
				else {
					$sizes[ $s ]['crop'] = get_option( "{$s}_crop" );
				} // For default sizes set in options
			}

			$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );

			// Only if not all sizes
			if ( isset( $thumbnails ) && is_array( $thumbnails ) && isset( $meta_datas['sizes'] ) && ! empty( $meta_datas['sizes'] ) ) {
				// Fill the array with the other sizes not have to be done
				foreach ( $meta_datas['sizes'] as $name => $fsize ) {
					$metadata['sizes'][ $name ] = $fsize;
				}
			}

			foreach ( $sizes as $size => $size_data ) {
				if ( isset( $thumbnails ) ) {
					if ( ! in_array( $size, $thumbnails ) ) {
						continue;
					}
				}

				$resized = image_make_intermediate_size( $file, $size_data['width'], $size_data['height'], $size_data['crop'] );

				if ( isset( $meta_datas['size'][ $size ] ) ) {
					// Remove the size from the orignal sizes for after work
					unset( $meta_datas['size'][ $size ] );
				}

				if ( $resized ) {
					$metadata['sizes'][ $size ] = $resized;
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
}