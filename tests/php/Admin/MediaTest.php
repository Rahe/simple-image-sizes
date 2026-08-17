<?php
/**
 * Tests for media settings and the Mediapapa notice.
 *
 * @package Simple_Image_Sizes
 */

namespace Rahe\Simple_Image_Sizes\Tests\Admin;

use Rahe\Simple_Image_Sizes\Admin\Media;
use Rahe\Simple_Image_Sizes\Main;
use WP_UnitTestCase;

/**
 * Test media administration behavior.
 */
class MediaTest extends WP_UnitTestCase {

	/**
	 * Administrator used by notice tests.
	 *
	 * @var int
	 */
	private $administrator_id;

	/**
	 * Prepare an administrator and the plugins screen.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->administrator_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->administrator_id );
		set_current_screen( 'plugins' );
	}

	/**
	 * Restore shared WordPress state.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'sis_mediapapa_notice_cta_url' );
		remove_all_filters( 'sis_mediapapa_plugin_bootstrap_paths' );
		delete_user_meta( $this->administrator_id, Main::MEDIAPAPA_NOTICE_META_KEY );
		wp_dequeue_script( 'sis_mediapapa_notice' );
		wp_deregister_script( 'sis_mediapapa_notice' );
		set_current_screen( 'front' );

		parent::tear_down();
	}

	/**
	 * The settings link is added only to this plugin's row.
	 *
	 * @return void
	 */
	public function test_add_settings_link_targets_simple_image_sizes() {
		$links  = [ '<a href="#">Deactivate</a>' ];
		$result = Media::add_settings_link( $links, 'simple-image-sizes/simple_image_sizes.php' );

		$this->assertCount( 2, $result );
		$this->assertStringContainsString( admin_url( 'options-media.php' ), $result[0] );
		$this->assertStringContainsString( 'Settings', $result[0] );
		$this->assertSame( $links, Media::add_settings_link( $links, 'another/plugin.php' ) );
	}

	/**
	 * Eligible administrators see the dismissible notice on the plugins screen.
	 *
	 * @return void
	 */
	public function test_render_mediapapa_notice_for_eligible_administrator() {
		ob_start();
		Media::render_mediapapa_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="sis-mediapapa-notice"', $output );
		$this->assertStringContainsString( 'notice-info is-dismissible', $output );
		$this->assertStringContainsString( esc_url( SIS_MEDIAPAPA_CTA_URL ), $output );
		$this->assertStringContainsString( 'noopener noreferrer', $output );
	}

	/**
	 * A dismissal applies to the current plugin version.
	 *
	 * @return void
	 */
	public function test_render_mediapapa_notice_hides_currently_dismissed_version() {
		update_user_meta( $this->administrator_id, Main::MEDIAPAPA_NOTICE_META_KEY, SIS_VERSION );

		ob_start();
		Media::render_mediapapa_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * The notice is hidden outside the plugins screen and for non-admin users.
	 *
	 * @return void
	 */
	public function test_render_mediapapa_notice_checks_screen_and_capability() {
		set_current_screen( 'upload' );

		ob_start();
		Media::render_mediapapa_notice();
		$wrong_screen_output = ob_get_clean();

		set_current_screen( 'plugins' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		ob_start();
		Media::render_mediapapa_notice();
		$subscriber_output = ob_get_clean();

		$this->assertSame( '', $wrong_screen_output );
		$this->assertSame( '', $subscriber_output );
	}

	/**
	 * The notice is hidden when a configured Mediapapa-family plugin is active.
	 *
	 * @return void
	 */
	public function test_render_mediapapa_notice_hides_when_family_plugin_is_active() {
		update_option( 'active_plugins', [ 'simple-image-sizes/simple_image_sizes.php' ] );
		add_filter(
			'sis_mediapapa_plugin_bootstrap_paths',
			static function () {
				return [ 'simple-image-sizes/simple_image_sizes.php' ];
			}
		);

		ob_start();
		Media::render_mediapapa_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Dismissal JavaScript is enqueued only where the notice can appear.
	 *
	 * @return void
	 */
	public function test_enqueue_notice_dismiss_adds_inline_ajax_request() {
		Media::enqueue_notice_dismiss( 'plugins.php' );

		$inline_scripts = wp_scripts()->get_data( 'sis_mediapapa_notice', 'after' );
		$inline_script  = implode( "\n", $inline_scripts );

		$this->assertTrue( wp_script_is( 'sis_mediapapa_notice', 'enqueued' ) );
		$this->assertStringContainsString( 'sis_dismiss_mediapapa_notice', $inline_script );
		$this->assertStringContainsString( trim( wp_json_encode( admin_url( 'admin-ajax.php' ) ), '"' ), $inline_script );
	}
}
