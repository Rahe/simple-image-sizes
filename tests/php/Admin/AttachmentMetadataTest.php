<?php
/**
 * Tests for attachment metadata regeneration.
 *
 * @package Simple_Image_Sizes
 */

namespace Rahe\Simple_Image_Sizes\Tests\Admin;

use Rahe\Simple_Image_Sizes\Admin\Main;
use WP_UnitTestCase;

/**
 * Test attachment metadata preservation.
 */
class AttachmentMetadataTest extends WP_UnitTestCase {

	/**
	 * Remove filters installed by tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'intermediate_image_sizes' );
		parent::tear_down();
	}

	/**
	 * Full regeneration replaces sizes while preserving attachment-level data.
	 *
	 * @return void
	 */
	public function test_full_regeneration_preserves_attachment_level_metadata() {
		$attachment_id = $this->create_image_attachment();
		$metadata      = [
			'file'           => '2026/08/canola.jpg',
			'width'          => 640,
			'height'         => 480,
			'filesize'       => 123456,
			'original_image' => 'canola-original.jpg',
			'sizes'          => [
				'old-size' => [ 'file' => 'canola-old.jpg', 'width' => 100, 'height' => 100 ],
			],
		];
		wp_update_attachment_metadata( $attachment_id, $metadata );
		add_filter( 'intermediate_image_sizes', '__return_empty_array' );

		$result = Main::wp_generate_attachment_metadata_custom(
			$attachment_id,
			get_attached_file( $attachment_id )
		);

		$this->assertSame( 123456, $result['filesize'] );
		$this->assertSame( 'canola-original.jpg', $result['original_image'] );
		$this->assertArrayNotHasKey( 'sizes', $result );
	}

	/**
	 * Selective regeneration keeps sizes that were not requested.
	 *
	 * @return void
	 */
	public function test_selective_regeneration_keeps_existing_sizes() {
		$attachment_id = $this->create_image_attachment();
		$existing_sizes = [
			'keep-me' => [ 'file' => 'canola-kept.jpg', 'width' => 100, 'height' => 100 ],
		];
		wp_update_attachment_metadata(
			$attachment_id,
			[
				'file'  => '2026/08/canola.jpg',
				'sizes' => $existing_sizes,
			]
		);
		add_filter( 'intermediate_image_sizes', '__return_empty_array' );

		$result = Main::wp_generate_attachment_metadata_custom(
			$attachment_id,
			get_attached_file( $attachment_id ),
			[]
		);

		$this->assertSame( $existing_sizes, $result['sizes'] );
	}

	/**
	 * Create an attachment backed by a WordPress test image.
	 *
	 * @return int
	 */
	private function create_image_attachment() {
		$file = DIR_TESTDATA . '/images/canola.jpg';
		$id   = wp_insert_attachment(
			[
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Canola',
				'post_status'    => 'inherit',
			],
			$file
		);

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		return $id;
	}
}
