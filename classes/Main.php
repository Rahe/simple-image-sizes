<?php
namespace Rahe\Simple_Image_Sizes;

class Main {
	public const MEDIAPAPA_NOTICE_META_KEY = 'sis_mediapapa_notice_dismissed_version';

	public function __construct() {
		// Make new image sizes.
		add_action( 'init', [ __CLASS__, 'after_setup_theme' ], 1 );

		// Add translation.
		add_action( 'init', [ __CLASS__, 'init_translation' ], 2 );

		// Register plugin metadata.
		add_action( 'init', [ __CLASS__, 'register_notice_meta' ], 3 );
	}

	/**
	 * Register the per-user notice dismissal state.
	 *
	 * @return void
	 */
	public static function register_notice_meta() {
		register_meta(
			'user',
			self::MEDIAPAPA_NOTICE_META_KEY,
			[
				'type'              => 'string',
				'description'       => 'Simple Image Sizes version for which the notice was dismissed.',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => false,
			]
		);
	}

	/**
	 * Override the images by the plugin images
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function after_setup_theme() {
		// Get initial options.
		$sizes = get_option( SIS_OPTION, [] );

		// Return false if empty.
		if ( empty( $sizes ) || ! is_array( $sizes ) ) {
			return;
		}

		// Set the new sizes.
		foreach ( $sizes as $name => $size ) {
			if ( empty( $size ) || ! isset( $size['w'] ) || ! isset( $size['h'] ) ) {
				continue;
			}

			$crop = ( isset( $size['c'] ) && ! empty( $size['c'] ) ) ? $size['c'] : false;

			if ( is_bool( $crop ) || is_numeric( $crop ) ) {
				$crop = ( 0 === absint( $crop ) ) ? false : true;
			} elseif ( is_string( $crop ) ) {
				$crop = explode( '_', $crop );
			}

			// Add the images sizes.
			add_image_size( $name, $size['w'], $size['h'], $crop );
		}
	}

	/**
	 * Load the plugin text domain
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function init_translation() {
		load_plugin_textdomain( 'simple-image-sizes', false, basename( rtrim( SIS_DIR, '/' ) ) . '/languages' );
	}
}
