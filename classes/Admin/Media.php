<?php
namespace Rahe\Simple_Image_Sizes\Admin;

class Media {

	/**
	 * Original WordPress sizes.
	 *
	 * @var array
	 */
	public static $original = [
		'thumbnail',
		'medium',
		'large',
	];

	public function __construct() {
		// Init.
		add_action( 'admin_menu', [ __CLASS__, 'init' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ], 11 );

		// Add ajax action.
		add_action( 'wp_ajax_sis_get_list', [ __CLASS__, 'a_get_list' ] );
		add_action( 'wp_ajax_sis_rebuild_images', [ __CLASS__, 'a_thumbnails_rebuild' ] );
		add_action( 'wp_ajax_sis_get_sizes', [ __CLASS__, 'a_get_sizes' ] );
		add_action( 'wp_ajax_sis_add_size', [ __CLASS__, 'a_add_size' ] );
		add_action( 'wp_ajax_sis_remove_size', [ __CLASS__, 'a_remove_size' ] );
		add_action( 'wp_ajax_sis_dismiss_mediapapa_notice', [ __CLASS__, 'a_dismiss_mediapapa_notice' ] );

		// Add link in plugins list.
		add_filter( 'plugin_action_links', [ __CLASS__, 'add_settings_link' ], 10, 2 );
		add_filter( 'plugin_row_meta', [ __CLASS__, 'add_plugin_row_meta' ], 10, 2 );
		add_action( 'all_admin_notices', [ __CLASS__, 'render_mediapapa_notice' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_notice_dismiss' ], 20 );
	}

	/**
	 * Register javascripts and css.
	 *
	 * @access public
	 *
	 * @param string $hook_suffix
	 *
	 * @author Nicolas Juen
	 */
	public static function enqueue_assets( $hook_suffix = '' ) {
		if ( ! isset( $hook_suffix ) || empty( $hook_suffix ) ) {
			return;
		}

		$suffixes = [ 'options-media.php', 'settings_page_media' ];
		if ( ! in_array( $hook_suffix, $suffixes, true ) ) {
			return;
		}

		// Add javascript.
		wp_enqueue_script( 'sis_js' );

		// Add CSS.
		wp_enqueue_style( 'sis_css' );

		// Add underscore template.
		add_action( 'admin_footer', [ 'Rahe\Simple_Image_Sizes\Admin\Main', 'add_template' ] );
		// Add section for the thumbnail regeneration.
		add_settings_section(
			'thumbnail_regenerate',
			__( 'Thumbnail regeneration', 'simple-image-sizes' ),
			[
				__CLASS__,
				'thumbnailRegenerate',
			],
			'media'
		);

	}

	/**
	 * Enqueue dismiss script for Mediapapa notice.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function enqueue_notice_dismiss( $hook_suffix = '' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! in_array( (string) $hook_suffix, [ 'upload.php', 'options-media.php', 'settings_page_media' ], true ) ) {
			return;
		}

		wp_register_script( 'sis_mediapapa_notice', false, [ 'jquery' ], SIS_VERSION, true );
		wp_enqueue_script( 'sis_mediapapa_notice' );
		$js = sprintf(
			'jQuery(function($){$(document).on("click","#sis-mediapapa-notice .notice-dismiss",function(){$.post(%1$s,{action:%2$s,nonce:%3$s});});});',
			wp_json_encode( admin_url( 'admin-ajax.php' ) ),
			wp_json_encode( 'sis_dismiss_mediapapa_notice' ),
			wp_json_encode( wp_create_nonce( 'sis_dismiss_mediapapa_notice' ) )
		);
		wp_add_inline_script( 'sis_mediapapa_notice', $js );
	}

	/**
	 * Display Mediapapa notice on media screens.
	 *
	 * @return void
	 */
	public static function render_mediapapa_notice() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || empty( $screen->id ) || ! in_array( $screen->id, [ 'upload', 'options-media' ], true ) ) {
			return;
		}

		if ( self::is_mediapapa_family_active() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		$dismissed_version = (string) get_user_meta( $user_id, 'sis_mediapapa_notice_dismissed_version', true );
		$dismissed_cycle   = (int) get_user_meta( $user_id, 'sis_mediapapa_notice_dismissed_cycle', true );
		$current_cycle     = (int) get_option( 'sis_mediapapa_notice_cycle', 0 );
		if ( (string) SIS_VERSION === $dismissed_version && $current_cycle <= $dismissed_cycle ) {
			return;
		}

		$cta_url = apply_filters(
			'sis_mediapapa_notice_cta_url',
			defined( 'SIS_MEDIAPAPA_CTA_URL' ) ? SIS_MEDIAPAPA_CTA_URL : 'https://www.wp-mediapapa.com/simple-image-sizes/'
		);
		if ( ! is_string( $cta_url ) || '' === $cta_url ) {
			return;
		}

		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $cta_url ),
			esc_html__( 'Mediapapa on WordPress.org', 'simple-image-sizes' )
		);
		$message = sprintf(
			/* translators: %s: HTML link to Mediapapa. */
			__( 'Hi, I\'m Nicolas Juen, the author of <strong>Simple Image Sizes</strong>. I\'m commited to keeping the plugin <strong>free</strong>, and I continue maintaining it as long as it will remain usefull. About 2 years ago, I started working on a new projet called <strong>Mediapapa</strong>, which now have a <strong>free</strong> version to better understand and organize your WordPress Media Library. I would be grateful for you to test it and share your feedback: %s Thank you for your trust.', 'simple-image-sizes' ),
			$link
		);

		echo '<div id="sis-mediapapa-notice" class="notice notice-warning is-dismissible"><p>';
		echo wp_kses(
			$message,
			[
				'strong' => [],
				'a'      => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
			]
		);
		echo '</p></div>';
	}

	/**
	 * Whether Mediapapa (free or Pro) is active — notice should stay hidden.
	 *
	 * @return bool
	 */
	private static function is_mediapapa_family_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Paths match mediapapa-org / mediapapa-pro repos: bootstrap is always `mediapapa.php` (folder name varies).
		$explicit = apply_filters(
			'sis_mediapapa_plugin_bootstrap_paths',
			array(
				'mediapapa/mediapapa.php',       // WordPress.org + typical folder name.
				'mediapapa-pro/mediapapa.php',   // Pro distribution (GitHub mediapapa-pro).
				'mediapapa-org/mediapapa.php',   // Dev clone of mediapapa-org repo.
			)
		);
		foreach ( $explicit as $plugin_file ) {
			if ( is_string( $plugin_file ) && '' !== $plugin_file && is_plugin_active( $plugin_file ) ) {
				return true;
			}
		}

		$active = array_merge(
			(array) get_option( 'active_plugins', array() ),
			array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) )
		);
		foreach ( $active as $plugin_file ) {
			if ( ! is_string( $plugin_file ) ) {
				continue;
			}
			// mediapapa/, mediapapa-pro/, mediapapa-org/, mediapapa_pro/, etc.
			if ( preg_match( '#^mediapapa(-(org|pro)|_pro)?/#i', $plugin_file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Dismiss Mediapapa notice.
	 *
	 * @return void
	 */
	public static function a_dismiss_mediapapa_notice() {
		if ( ! check_ajax_referer( 'sis_dismiss_mediapapa_notice', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( null, 400 );
		}

		update_user_meta( $user_id, 'sis_mediapapa_notice_dismissed_version', (string) SIS_VERSION );
		update_user_meta( $user_id, 'sis_mediapapa_notice_dismissed_cycle', (int) get_option( 'sis_mediapapa_notice_cycle', 0 ) );
		wp_send_json_success();
	}


	/**
	 * Add a link to the setting option page
	 *
	 * @access public
	 *
	 * @param array $links : the admin links.
	 * @param string $file : the file concerned in the row.
	 *
	 * @return array
	 * @author Nicolas Juen
	 */
	public static function add_settings_link( $links = array(), $file = '' ) {
		if ( 'simple-image-sizes/simple_image_sizes.php' !== $file ) {
			return $links;
		}

		$settings_link = sprintf( '<a href="%s"> %s </a>', admin_url( 'options-media.php' ), __( 'Settings', 'simple-image-sizes' ) );
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add plugin row meta links on plugins.php.
	 *
	 * @param array  $links Existing meta links.
	 * @param string $file  Current plugin file basename.
	 * @return array
	 */
	public static function add_plugin_row_meta( $links = array(), $file = '' ) {
		if ( 'simple-image-sizes/simple_image_sizes.php' !== $file ) {
			return $links;
		}

		$plugin_site_url = apply_filters(
			'sis_mediapapa_notice_cta_url',
			defined( 'SIS_MEDIAPAPA_CTA_URL' ) ? SIS_MEDIAPAPA_CTA_URL : 'https://www.wp-mediapapa.com/simple-image-sizes/'
		);
		if ( ! is_string( $plugin_site_url ) || '' === $plugin_site_url ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $plugin_site_url ),
			esc_html__( 'Visit plugin site', 'simple-image-sizes' )
		);

		return $links;
	}

	/**
	 * Init for the option page
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function init() {
		// Check if admin.
		if ( ! is_admin() ) {
			return;
		}

		// Get the image sizes.
		global $_wp_additional_image_sizes;

		// Get the sizes and add the settings.
		foreach ( get_intermediate_image_sizes() as $s ) {
			// Don't make the original sizes or numeric sizes that appear.
			if ( in_array( $s, self::$original ) || is_integer( $s ) ) {
				continue;
			}

			// Set width.
			$width = isset( $_wp_additional_image_sizes[ $s ]['width'] ) ? intval( $_wp_additional_image_sizes[ $s ]['width'] ) : get_option( "{$s}_size_w" );

			// Set height.
			$height = isset( $_wp_additional_image_sizes[ $s ]['height'] ) ? intval( $_wp_additional_image_sizes[ $s ]['height'] ) : get_option( "{$s}_size_h" );

			// Set crop.
			$crop = isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ? intval( $_wp_additional_image_sizes[ $s ]['crop'] ) : get_option( "{$s}_crop" );

			// Add the setting field for this size.
			add_settings_field(
				'image_size_' . $s,
				/* translators: %s is the image size slug. */
				sprintf( __( '%s size', 'simple-image-sizes' ), esc_html( $s ) ),
				[
					__CLASS__,
					'image_sizes',
				],
				'media',
				'default',
				[
					'name'   => $s,
					'width'  => $width,
					'height' => $height,
					'c'      => $crop,
				]
			);
		}

		// Register the setting for media option page.
		register_setting( 'media', SIS_OPTION );

		// Add the button.
		add_settings_field(
			'add_size_button',
			__( 'Add a new size', 'simple-image-sizes' ),
			[
				__CLASS__,
				'addSizeButton',
			],
			'media'
		);

		// Add php button.
		add_settings_field(
			'get_php_button',
			__( 'Get PHP for theme', 'simple-image-sizes' ),
			[
				__CLASS__,
				'getPhpButton',
			],
			'media'
		);

	}

	/**
	 * Display the row of the image size
	 *
	 * @access public
	 *
	 * @param mixed $args
	 *
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function image_sizes( $args ) {
		if ( is_integer( $args['name'] ) ) {
			return;
		}

		// Get the options.
		$sizes = (array) get_option( SIS_OPTION, [] );

		// Get the vars.
		$height = isset( $sizes[ $args['name'] ]['h'] ) ? $sizes[ $args['name'] ]['h'] : $args['height'];
		$width  = isset( $sizes[ $args['name'] ]['w'] ) ? $sizes[ $args['name'] ]['w'] : $args['width'];
		$crop   = isset( $sizes[ $args['name'] ]['c'] ) && ! empty( $sizes[ $args['name'] ]['c'] ) ? $sizes[ $args['name'] ]['c'] : $args['c'];
		$show   = isset( $sizes[ $args['name'] ]['s'] ) && ! empty( $sizes[ $args['name'] ]['s'] ) ? '1' : '0';
		$custom = isset( $sizes[ $args['name'] ]['custom'] ) && ! empty( $sizes[ $args['name'] ]['custom'] ) ? '1' : '0';
		$name   = isset( $sizes[ $args['name'] ]['n'] ) && ! empty( $sizes[ $args['name'] ]['n'] ) ? esc_html( $sizes[ $args['name'] ]['n'] ) : esc_html( $args['name'] );
		?>
		<input type="hidden" value="<?php echo esc_attr( $args['name'] ); ?>" name="image_name"/>
		<?php if ( $custom ) : ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][custom]' ); ?>" type="hidden"
				   id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][custom]' ); ?>" value="1"/>
		<?php else : ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][theme]' ); ?>" type="hidden"
				   id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][theme]' ); ?>" value="1"/>
		<?php endif; ?>
		<label class="sis-label" for="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][w]' ); ?>">
			<?php _e( 'Maximum width', 'simple-image-sizes' ); ?>
			<input name="<?php esc_attr_e( 'custom_image_sizes[' . $args['name'] . '][w]' ); ?>" class='w small-text'
				   type="number" step='1' min='0'
				   id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][w]' ); ?>"
				   base_w='<?php echo esc_attr( $width ); ?>' value="<?php echo esc_attr( $width ); ?>"/>
		</label>
		<label class="sis-label" for="<?php esc_attr_e( 'custom_image_sizes[' . $args['name'] . '][h]' ); ?>">
			<?php _e( 'Maximum height', 'simple-image-sizes' ); ?>
			<input name="<?php esc_attr_e( 'custom_image_sizes[' . $args['name'] . '][h]' ); ?>" class='h small-text'
				   type="number" step='1' min='0'
				   id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][h]' ); ?>"
				   base_h='<?php echo esc_attr( $height ); ?>' value="<?php echo esc_attr( $height ); ?>"/>
		</label>
		<label class="sis-label" for="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][n]' ); ?>">
			<?php _e( 'Public name', 'simple-image-sizes' ); ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][n]' ); ?>" class='n'
				   type="text" id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][n]' ); ?>"
				   base_n='<?php echo $name; ?>' value="<?php echo $name; ?>"/>
		</label>
		<span class="size_options">
			<label class="c"
				   for="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][c]' ); ?>"><?php _e( 'Cropping', 'simple-image-sizes' ); ?></label>
			<select id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][c]' ); ?>" class="c crop"
					base_c='<?php echo esc_attr( $crop ); ?>'
					name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][c]' ); ?>">

				<?php foreach ( Main::get_available_crop() as $crop_position => $label ) : ?>
					<option <?php selected( $crop_position, $crop ); ?>
							value="<?php echo esc_attr( $crop_position ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			
			<input type='checkbox'
				   id="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][s]' ); ?>" <?php checked( $show, 1 ); ?>
				   class="s show" base_s='<?php echo esc_attr( $show ); ?>'
				   name="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][s]' ); ?>" value="1"/>
			<label class="s"
				   for="<?php echo esc_attr( 'custom_image_sizes[' . $args['name'] . '][s]' ); ?>"><?php _e( 'Show in post insertion?', 'simple-image-sizes' ); ?></label>
		</span>
		<span class="delete_size  button-secondary"><?php _e( 'Delete', 'simple-image-sizes' ); ?></span>
		<span class="add_size validate_size button-primary"><?php _e( 'Update', 'simple-image-sizes' ); ?></span>

		<input type="hidden" class="deleteSize button-primary"
			   value='<?php echo wp_create_nonce( 'delete_' . $args['name'] ); ?>'/>
		<?php
	}

	/**
	 * Add the button to add a size
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function addSizeButton() {
		?>
		<input type="button" class="button-secondary action" id="add_size"
			   value="<?php esc_attr_e( 'Add a new size of thumbnail', 'simple-image-sizes' ); ?>"/>
		<?php
	}

	/**
	 * Add the button to get the PHP for the sizes
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function getPhpButton() {
		?>
		<input type="button" class="button-secondary action" id="get_php"
			   value="<?php esc_attr_e( 'Get the PHP for the theme', 'simple-image-sizes' ); ?>"/>
		<p> <?php _e( 'Copy and paste the code below into your theme\'s functions file if you want to save them and deactivate the plugin.', 'simple-image-sizes' ); ?> </p>
		<code id="sis_get_php"></code>
		<?php
	}

	/**
	 * Display the Table of sizes and post types for regenerating
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function thumbnailRegenerate() {
		if ( is_file( SIS_DIR . '/templates/options-media.php' ) ) {
			include SIS_DIR . '/templates/options-media.php';
		} else {
			esc_html_e( 'Admin option-media template missing', 'simple-image-sizes' );
		}
	}

	/**
	 * Add a size by Ajax
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_add_size() {

		// Get the nonce
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

		// Get old options
		$sizes              = (array) get_option( SIS_OPTION, [] );
		$croppings          = Main::get_available_crop();
		$croppings[ true ]  = '';
		$croppings[ false ] = '';

		// Check entries
		$name   = isset( $_POST['name'] ) ? sanitize_text_field( sanitize_title( $_POST['name'] ) ) : '';
		$height = ! isset( $_POST['height'] ) ? 0 : absint( $_POST['height'] );
		$width  = ! isset( $_POST['width'] ) ? 0 : absint( $_POST['width'] );
		$crop   = isset( $_POST['crop'] ) && isset( $croppings[ $_POST['crop'] ] ) ? (bool) $_POST['crop'] : false;
		$show   = ! ( isset( $_POST['show'] ) && $_POST['show'] == 'false' );
		$cn     = isset( $_POST['customName'] ) && ! empty( $_POST['customName'] ) ? sanitize_text_field( $_POST['customName'] ) : $name;

		// Check the nonce
		if ( ! wp_verify_nonce( $nonce, 'add_size' ) || ! current_user_can( 'manage_options' ) ) {
			die( 0 );
		}

		// If no name given do not save
		if ( empty( $name ) ) {
			die( 0 );
		}

		// Make values
		$values = [
			'custom' => 1,
			'w'      => $width,
			'h'      => $height,
			'c'      => $crop,
			's'      => $show,
			'n'      => $cn,
		];

		// If the size have not changed return 2
		if ( isset( $sizes[ $name ] ) && $sizes[ $name ] === $values ) {
			die( 2 );
		}

		// Put the new values
		$sizes[ $name ] = $values;

		// display update result
		echo (int) update_option( 'custom_image_sizes', $sizes );
		die();
	}

	/**
	 * Remove a size by Ajax
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_remove_size() {

		// Get old options
		$sizes = (array) get_option( SIS_OPTION, [] );

		// Get the nonce and name
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$name  = isset( $_POST['name'] ) ? sanitize_title( $_POST['name'] ) : '';

		// Check the nonce.
		if ( ! wp_verify_nonce( $nonce, 'delete_' . $name ) || ! current_user_can( 'manage_options' ) ) {
			die( 0 );
		}

		// Remove the size
		unset( $sizes[ sanitize_title( $name ) ] );
		unset( $sizes[0] );

		// Display the results
		echo (int) update_option( SIS_OPTION, $sizes );
		die();
	}

	/**
	 * Display the add_image_size for the registered sizes
	 *
	 * @access public
	 * @return void
	 */
	public static function a_get_sizes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( 0 );
		}

		global $_wp_additional_image_sizes, $wp_version;

		foreach ( get_intermediate_image_sizes() as $s ) {
			// Don't make the original sizes
			if ( in_array( $s, self::$original ) ) {
				continue;
			}

			// Set width
			$width = isset( $_wp_additional_image_sizes[ $s ]['width'] ) ? intval( $_wp_additional_image_sizes[ $s ]['width'] ) : get_option( "{$s}_size_w" );

			// Set height
			$height = isset( $_wp_additional_image_sizes[ $s ]['height'] ) ? intval( $_wp_additional_image_sizes[ $s ]['height'] ) : get_option( "{$s}_size_h" );

			//Set crop
			$crop = isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ? $_wp_additional_image_sizes[ $s ]['crop'] : get_option( "{$s}_crop" );

			if ( is_bool( $crop ) || is_numeric( $crop ) || version_compare( $wp_version, '3.9', '<' ) ) {
				$crop = ( absint( $crop ) == 0 ) ? 'false' : 'true';
			} else {
				if ( ! Main::is_crop_position( implode( '_', $crop ) ) ) {
					$crop = 'false';
				} else {
					$crop = '[ "' . $crop[0] . '", "' . $crop[1] . '"]';
				}
			}
			?>
			add_image_size( '<?php echo $s; ?>', '<?php echo $width; ?>', '<?php echo $height; ?>', <?php echo $crop; ?> );
			<br/>
			<?php
		}

		die();
	}

	/**
	 *
	 * Get the media list to regenerate
	 *
	 * @param : void
	 *
	 * @return void
	 */
	public static function a_get_list() {
		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		// Basic vars
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

		// Check the nonce.
		if ( ! wp_verify_nonce( $nonce, 'getList' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [] );
		}

		if ( isset( $_POST['post_types'] ) && ! empty( $_POST['post_types'] ) ) {
			$valid_types = [];
			foreach ( (array) $_POST['post_types'] as $type ) {
				$type = sanitize_key( (string) $type );
				if ( $type && post_type_exists( $type ) ) {
					$valid_types[] = $type;
				}
			}
			$valid_types = array_values( array_unique( $valid_types ) );

			if ( empty( $valid_types ) ) {
				wp_send_json( [] );
			}

			// Get image medias.
			$whichmimetype = wp_post_mime_type_where( 'image', $wpdb->posts );
			$sanitized_in  = "'" . implode( "','", array_map( 'esc_sql', $valid_types ) ) . "'";

			// Get all parent from post type.
			$attachments = $wpdb->get_var(
				"SELECT COUNT( ID )
				FROM $wpdb->posts 
				WHERE 1 = 1
				AND post_type = 'attachment'
				$whichmimetype
				AND post_parent IN (
					SELECT DISTINCT ID 
					FROM $wpdb->posts 
					WHERE post_type IN ($sanitized_in)
				)"
			);
			// Return the Id's and Title of medias
			wp_send_json( [ 'total' => $attachments ] );
		} else {
			$attachments = get_children(
				[
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'numberposts'    => - 1,
					'post_status'    => null,
					'post_parent'    => null, // any parent
					'output'         => 'ids',
				]
			);
			// Return the Id's and Title of medias
			wp_send_json( [ 'total' => count( $attachments ) ] );
		}
	}

	/**
	 * Regenerate the thumbnails ajax action
	 *
	 * @param void
	 *
	 * @author Nicolas Juen
	 */
	public static function a_thumbnails_rebuild() {
		/**
		 * @var $wpdb \wpdb
		 */
		global $wpdb;

		// Get the nonce
		$nonce      = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$offset     = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$post_types = isset( $_POST['post_types'] ) ? $_POST['post_types'] : 'any';
		$thumbnails = isset( $_POST['thumbnails'] ) ? $_POST['thumbnails'] : null;

		// Check the nonce.
		if ( ! wp_verify_nonce( $nonce, 'regen' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'error' => __( 'Trying to cheat?', 'simple-image-sizes' ) ] );
		}

		if ( 'any' !== $post_types ) {
			$valid_types = [];
			foreach ( (array) $_POST['post_types'] as $type ) {
				$type = sanitize_key( (string) $type );
				if ( $type && post_type_exists( $type ) ) {
					$valid_types[] = $type;
				}
			}
			$valid_types = array_values( array_unique( $valid_types ) );

			if ( empty( $valid_types ) ) {
				wp_send_json( [] );
			}

			// Get image medias
			$whichmimetype = wp_post_mime_type_where( 'image', $wpdb->posts );
			$sanitized_in  = "'" . implode( "','", array_map( 'esc_sql', $valid_types ) ) . "'";

			// Get all parent from post type
			$attachment = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID
				FROM $wpdb->posts 
				WHERE 1 = 1
				AND post_type = 'attachment'
				$whichmimetype
				AND post_parent IN (
					SELECT DISTINCT ID 
					FROM $wpdb->posts 
					WHERE post_type IN ($sanitized_in)
				)
				LIMIT %d,1 
			",
					$offset
				)
			);
		} else {
			$attachment = get_posts(
				[
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'numberposts'    => 1,
					'post_status'    => 'any',
					'output'         => 'object',
					'offset'         => $offset,
				]
			);

			$attachment = ! empty( $attachment ) ? $attachment[0]->ID : 0;
		}

		if ( empty( $attachment ) ) {
			wp_send_json(
				[
					'message' => __( 'Regeneration ended', 'simple-image-sizes' ),
				]
			);
		}
		wp_send_json( Main::thumbnail_rebuild( $attachment, $thumbnails ) );
	}
}
