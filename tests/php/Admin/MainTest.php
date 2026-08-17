<?php
/**
 * Tests for admin image helpers.
 *
 * @package Simple_Image_Sizes
 */

namespace Rahe\Simple_Image_Sizes\Tests\Admin;

use Rahe\Simple_Image_Sizes\Admin\Main;
use WP_UnitTestCase;

/**
 * Test admin helpers.
 */
class MainTest extends WP_UnitTestCase {

	/**
	 * All supported crop positions are exposed.
	 *
	 * @return void
	 */
	public function test_get_available_crop_returns_boolean_and_positioned_crops() {
		$crops = Main::get_available_crop();

		$this->assertCount( 11, $crops );
		$this->assertArrayHasKey( 0, $crops );
		$this->assertArrayHasKey( 1, $crops );
		$this->assertSame( 'Left top', $crops['left_top'] );
		$this->assertSame( 'Center center', $crops['center_center'] );
		$this->assertSame( 'Right bottom', $crops['right_bottom'] );
	}

	/**
	 * Crop-position validation accepts known values and booleans only.
	 *
	 * @return void
	 */
	public function test_is_crop_position_validates_crop_values() {
		$this->assertTrue( Main::is_crop_position( true ) );
		$this->assertFalse( Main::is_crop_position( false ) );
		$this->assertTrue( Main::is_crop_position( 'center_top' ) );
		$this->assertFalse( Main::is_crop_position( 'outside_middle' ) );
	}

	/**
	 * Unknown positions have no public label.
	 *
	 * @return void
	 */
	public function test_get_crop_position_label_returns_label_or_empty_string() {
		$this->assertSame( 'Right center', Main::get_crop_position_label( 'right_center' ) );
		$this->assertSame( '', Main::get_crop_position_label( 'unknown' ) );
	}

	/**
	 * Localized script data contains messages, crop positions, and a valid nonce.
	 *
	 * @return void
	 */
	public function test_localize_vars_returns_complete_script_configuration() {
		$vars = Main::localize_vars();

		$this->assertSame( 'Reading attachments...', $vars['reading'] );
		$this->assertSame( Main::get_available_crop(), $vars['crop_positions'] );
		$this->assertSame( 1, wp_verify_nonce( $vars['regen_one'], 'regen' ) );
	}
}
