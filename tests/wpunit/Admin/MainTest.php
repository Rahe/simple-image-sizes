<?php

namespace Rahe\Simple_Image_Sizes\Tests\Admin;

use Codeception\TestCase\WPTestCase;
use Rahe\Simple_Image_Sizes\Admin\Main;
use WP_Post;
use WpunitTester;
use function tad\WPBrowser\realpathish;
use function var_dump;

class MainTest extends WPTestCase {
	/**
	 * @var WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		// Before...
		parent::setUp();
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		// Your set up methods here.
	}

	public function tearDown(): void {
		// Your tear down methods here.

		// Then...
		parent::tearDown();

	}

	// Tests
	public function testAvailableCrops() {
		$crops = Main::get_available_crop();

		$this->assertEquals(
			[
				'left_top'      => 'Left Top',
				'left_center'   => 'Left Center',
				'left_top'      => 'Left top',
				'left_center'   => 'Left center',
				0               => 'No',
				1               => 'Yes',
				'left_bottom'   => 'Left bottom',
				'center_top'    => 'Center top',
				'center_center' => 'Center center',
				'center_bottom' => 'Center bottom',
				'right_top'     => 'Right top',
				'right_center'  => 'Right center',
				'right_bottom'  => 'Right bottom',
			],
			$crops
		);
	}

	// Tests
	public function testAvailableCropsWPOlder39() {
		global $wp_version;
		$old_wp_version = $wp_version;
		$wp_version     = 3.1;
		$crops          = Main::get_available_crop();

		$this->assertEmpty( $crops );
		$wp_version = $old_wp_version;
	}

	public function testCropPosition() {
		$this->assertTrue( Main::is_crop_position( 'left_top' ) );
		$this->assertTrue( Main::is_crop_position( true ) );
		$this->assertFalse( Main::is_crop_position( false ) );
		$this->assertFalse( Main::is_crop_position( 'noexisting' ) );
	}

	public function testCropPositionLabel() {
		$this->assertEquals( 'Left top', Main::get_crop_position_label( 'left_top' ) );
		$this->assertEquals( 'Yes', Main::get_crop_position_label( true ) );
		$this->assertEmpty( Main::get_crop_position_label( false ) );
		$this->assertEmpty( Main::get_crop_position_label( 'noexisting' ) );
	}

	public function testThumbnailRebuildNoID() {
		$this->assertArrayHasKey( 'error', Main::thumbnail_rebuild( 0, 'thumbnail' ) );
	}

	public function testThumbnailRebuildNoFile() {
		$element = static::factory()->attachment->create_and_get();
		$result  = Main::thumbnail_rebuild( $element->ID, ['thumbnail'] );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertArrayHasKey( 'src', $result );
		$this->assertArrayHasKey( 'time', $result );
	}

	public function testThumbnailRebuildFile() {
		$element = static::factory()->attachment->create_upload_object( __DIR__.'/../../_data/image.jpg');
		$result  = Main::thumbnail_rebuild( $element, ['thumbnail'] );
		$this->assertArrayHasKey( 'title', $result );
		$this->assertArrayHasKey( 'src', $result );
		$this->assertArrayHasKey( 'time', $result );
	}

	public function testThumbnailRebuildFileAlreadyBuilt() {
		$element = static::factory()->attachment->create_upload_object( __DIR__.'/../../_data/image.jpg');
		Main::thumbnail_rebuild( $element, ['thumbnail'] );
		$result  = Main::thumbnail_rebuild( $element, ['thumbnail'] );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertArrayHasKey( 'src', $result );
		$this->assertArrayHasKey( 'time', $result );
	}
}
