<?php


use Codeception\TestCase\WPTestCase;
use Rahe\Simple_Image_Sizes\Main;

class MainTest extends WPTestCase {

	/**
	 * @var WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		// Before...
		parent::setUp();

		// Your set up methods here.
	}

	public function tearDown(): void {
		// Your tear down methods here.

		// Then...
		parent::tearDown();
	}

	public function testNoSizesAdded() {
		update_option( SIS_OPTION, [] );

		// Empty options
		$sizes = wp_get_additional_image_sizes();
		Main::after_setup_theme();
		$this->assertEquals( $sizes, wp_get_additional_image_sizes() );

		// Not array
		update_option( SIS_OPTION, 'text' );
		Main::after_setup_theme();
		$this->assertEquals( $sizes, wp_get_additional_image_sizes() );

		// No w
		update_option( SIS_OPTION, [
			'' =>
				[
					'custom' => 1,
					'w'      => 1,
					'c'      => '0',
					's'      => true,
					'n'      => 'size-test',
				],
		] );
		Main::after_setup_theme();
		$this->assertEquals( $sizes, wp_get_additional_image_sizes() );

		// No h
		update_option( SIS_OPTION, [
			'' =>
				[
					'custom' => 1,
					'h'      => 1,
					'c'      => '0',
					's'      => true,
					'n'      => 'size-test',
				],
		] );
		Main::after_setup_theme();
		$this->assertEquals( $sizes, wp_get_additional_image_sizes() );

	}

	public function testSizesAdded() {
		// Base size
		update_option( SIS_OPTION, [
			'size-test' =>
				[
					'custom' => 1,
					'w'      => 1,
					'h'      => 1,
					'c'      => '0',
					's'      => true,
					'n'      => 'size-test',
				],
		] );

		// The size is just added
		Main::after_setup_theme();
		$this->assertTrue( isset( wp_get_additional_image_sizes()['size-test'] ) );
	}

	public function testCropDefault() {
		//  No crop
		update_option( SIS_OPTION, [
			'size-test' =>
				[
					'custom' => 1,
					'w'      => 1,
					'h'      => 1,
					's'      => true,
					'n'      => 'size-test',
				],
		] );

		// Check crop
		Main::after_setup_theme();
		$this->assertFalse( wp_get_additional_image_sizes()['size-test']['crop'] );
	}

	public function testCropText() {
		//  Crop text
		update_option( SIS_OPTION, [
			'size-test' =>
				[
					'custom' => 1,
					'w'      => 1,
					'h'      => 1,
					's'      => true,
					'n'      => 'size-test',
					'c'      => 'top_left',
				],
		] );

		// Check crop
		Main::after_setup_theme();
		$this->assertTrue( is_array( wp_get_additional_image_sizes()['size-test']['crop'] ) );
		$this->assertEquals( [ 'top', 'left' ], wp_get_additional_image_sizes()['size-test']['crop'] );
	}
}