<?php
/**
 * Tests for post and attachment integrations.
 *
 * @package Simple_Image_Sizes
 */

namespace Rahe\Simple_Image_Sizes\Tests\Admin;

use Rahe\Simple_Image_Sizes\Admin\Post;
use WP_UnitTestCase;

/**
 * Test post editor integration.
 */
class PostTest extends WP_UnitTestCase {

	/**
	 * Remove options created by tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		delete_option( SIS_OPTION );
		parent::tear_down();
	}

	/**
	 * Only image sizes explicitly enabled for insertion are added.
	 *
	 * @return void
	 */
	public function test_add_thumbnail_name_adds_visible_custom_sizes() {
		update_option(
			SIS_OPTION,
			[
				'hero'    => [ 's' => 1, 'n' => 'Hero image' ],
				'card'    => [ 's' => '1' ],
				'private' => [ 's' => 0, 'n' => 'Private image' ],
			]
		);

		$result = Post::add_thumbnail_name( [ 'thumbnail' => 'Thumbnail' ] );

		$this->assertSame( 'Thumbnail', $result['thumbnail'] );
		$this->assertSame( 'Hero image', $result['hero'] );
		$this->assertSame( 'card', $result['card'] );
		$this->assertArrayNotHasKey( 'private', $result );
	}

	/**
	 * Existing size names are unchanged when no custom option exists.
	 *
	 * @return void
	 */
	public function test_add_thumbnail_name_preserves_existing_sizes_without_options() {
		$sizes = [ 'large' => 'Large' ];

		$this->assertSame( $sizes, Post::add_thumbnail_name( $sizes ) );
	}
}
