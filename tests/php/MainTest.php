<?php
/**
 * Tests for the main plugin class.
 *
 * @package Simple_Image_Sizes
 */

namespace Rahe\Simple_Image_Sizes\Tests;

use Rahe\Simple_Image_Sizes\Main;
use WP_UnitTestCase;

/**
 * Test the main plugin behavior.
 */
class MainTest extends WP_UnitTestCase {

	/**
	 * Image-size slugs registered by a test.
	 *
	 * @var string[]
	 */
	private $registered_sizes = [
		'sis-hard-crop',
		'sis-soft-crop',
		'sis-positioned-crop',
		'sis-invalid-size',
	];

	/**
	 * Clean test options and image sizes.
	 *
	 * @return void
	 */
	public function tear_down() {
		delete_option( SIS_OPTION );

		foreach ( $this->registered_sizes as $size ) {
			remove_image_size( $size );
		}

		parent::tear_down();
	}

	/**
	 * The constructor registers plugin initialization callbacks in order.
	 *
	 * @return void
	 */
	public function test_constructor_registers_initialization_hooks() {
		$this->assertSame( 1, has_action( 'init', [ Main::class, 'after_setup_theme' ] ) );
		$this->assertSame( 2, has_action( 'init', [ Main::class, 'init_translation' ] ) );
		$this->assertSame( 3, has_action( 'init', [ Main::class, 'register_notice_meta' ] ) );
	}

	/**
	 * Configured image sizes are registered and crop values are normalized.
	 *
	 * @return void
	 */
	public function test_after_setup_theme_registers_configured_image_sizes() {
		update_option(
			SIS_OPTION,
			[
				'sis-hard-crop'       => [ 'w' => 320, 'h' => 180, 'c' => '1' ],
				'sis-soft-crop'       => [ 'w' => 640, 'h' => 480, 'c' => '0' ],
				'sis-positioned-crop' => [ 'w' => 800, 'h' => 600, 'c' => 'left_top' ],
				'sis-invalid-size'    => [ 'w' => 120 ],
			]
		);

		Main::after_setup_theme();

		$this->assertSame(
			[ 'width' => 320, 'height' => 180, 'crop' => true ],
			$this->get_registered_size( 'sis-hard-crop' )
		);
		$this->assertSame(
			[ 'width' => 640, 'height' => 480, 'crop' => false ],
			$this->get_registered_size( 'sis-soft-crop' )
		);
		$this->assertSame(
			[ 'width' => 800, 'height' => 600, 'crop' => [ 'left', 'top' ] ],
			$this->get_registered_size( 'sis-positioned-crop' )
		);
		$this->assertFalse( has_image_size( 'sis-invalid-size' ) );
	}

	/**
	 * Empty and malformed options do not register image sizes.
	 *
	 * @return void
	 */
	public function test_after_setup_theme_ignores_invalid_options() {
		update_option( SIS_OPTION, 'invalid' );

		Main::after_setup_theme();

		$this->assertFalse( has_image_size( 'sis-invalid-size' ) );
	}

	/**
	 * Notice dismissal metadata is registered as private user metadata.
	 *
	 * @return void
	 */
	public function test_register_notice_meta_registers_expected_schema() {
		Main::register_notice_meta();

		$registered = get_registered_meta_keys( 'user' );
		$schema     = $registered[ Main::MEDIAPAPA_NOTICE_META_KEY ];

		$this->assertSame( 'string', $schema['type'] );
		$this->assertTrue( $schema['single'] );
		$this->assertSame( '', $schema['default'] );
		$this->assertFalse( $schema['show_in_rest'] );
		$this->assertSame( 'sanitize_text_field', $schema['sanitize_callback'] );
	}

	/**
	 * Return one registered additional image size.
	 *
	 * @param string $slug Image-size slug.
	 * @return array|null
	 */
	private function get_registered_size( $slug ) {
		global $_wp_additional_image_sizes;

		return isset( $_wp_additional_image_sizes[ $slug ] ) ? $_wp_additional_image_sizes[ $slug ] : null;
	}
}
