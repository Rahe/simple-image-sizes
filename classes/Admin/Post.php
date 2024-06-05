<?php
namespace Rahe\Simple_Image_Sizes\Admin;

class Post {
	public function __construct() {

		add_filter( 'image_size_names_choose', [ __CLASS__, 'add_thumbnail_name' ] );

		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ], 11 );

		// Rebuilt the image.
		add_action( 'wp_ajax_sis_rebuild_image', [ __CLASS__, 'a_thumbnail_rebuild' ] );

		// Rebuild featured.
		add_action( 'wp_ajax_sis_rebuild_featured', [ __CLASS__, 'a_featured_rebuild' ] );

		// Add action in media row quick actions.
		add_filter( 'media_row_actions', [ __CLASS__, 'add_actions_list' ], 10, 2 );

		// Add filter for the Media single.
		add_filter( 'attachment_fields_to_edit', [ __CLASS__, 'add_field_regenerate' ], 9, 2 );

		// Media regenerate on admin featured.
		add_filter( 'admin_post_thumbnail_html', [ __CLASS__, 'admin_post_thumbnail_html' ], 10, 2 );
	}

	/**
	 * Generate HTML on the featured image size.
	 *
	 * @param string $content : the content of the post_thumbnail view.
	 * @param int    $ID : the ID of the content concerned.
	 *
	 * @return string
	 */
	public static function admin_post_thumbnail_html( $content, $ID ) {
		/**
		 * Allow to not display the regenerate image link
		 */
		if ( false === apply_filters( 'SIS/Admin/Post/Display_Thumbnail_Regenerate', true ) ) {
			return $content;
		}

		/**
		 * Do not display if post_Type does not support it
		 */
		if ( false === post_type_supports( get_post_type(), 'thumbnail' ) ) {
			return $content;
		}

		$content .= '<span class="spinner"></span>';
		$content .= sprintf(
			"<a id='sis_featured_regenerate' data-nonce='%s' href='#' >%s</a>",
			wp_create_nonce( 'sis-regenerate-featured-' . $ID ),
			esc_html__( 'Regenerate image sizes', 'simple-image-sizes' )
		);
		$content .= '<div class="sis_message"></div>';

		return $content;
	}

	/**
	 * Rebuild the image size of a content featured image
	 */
	public static function a_featured_rebuild() {
		// Get the nonce.
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

		// Get the thumbnails.
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : null;

		// Check the nonce.
		if ( ! wp_verify_nonce( $nonce, 'sis-regenerate-featured-' . $id ) || ! \current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'error' => __( 'Trying to cheat ?', 'simple-image-sizes' ) ] );
		}

		$attachment_id = get_post_thumbnail_id( $id );

		if ( ! has_post_thumbnail( $id ) || is_null( $attachment_id ) ) {
			wp_send_json( [ 'error' => __( 'There is no media attached to this content.', 'simple-image-sizes' ) ] );
		}

		// Get the id.
		wp_send_json( Main::thumbnail_rebuild( $attachment_id ) );
	}

	/**
	 * Register javascripts and css.
	 *
	 * @access public
	 *
	 * @param string $hook_suffix : the hook for the current page.
	 *
	 * @author Nicolas Juen
	 */
	public static function enqueue_assets( $hook_suffix = '' ) {
		if ( ! isset( $hook_suffix ) || empty( $hook_suffix ) ) {
			return;
		}

		/**
		 * Enqueue the assets for the featured image only on the edit pages and the post types that supports it
		 */
		if ( in_array( $hook_suffix, [ 'post-new.php', 'post.php' ] ) ) {
			if ( post_type_supports( get_post_type( get_post() ), 'thumbnail' ) ) {
				// Add javascript.
				wp_enqueue_script( 'sis_js' );
			}
		}

		if ( 'upload.php' === $hook_suffix || ( 'post.php' === $hook_suffix && isset( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) ) {
			// Add javascript.
			wp_enqueue_script( 'sis_js' );

			// Add underscore template.
			add_action( 'admin_footer', [ 'Rahe\Simple_Image_Sizes\Admin\Main', 'add_template' ] );
		}
	}

	/**
	 * Rebuild the image
	 *
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function a_thumbnail_rebuild() {
		// Get the nonce.
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

		// Get the thumbnails.
		$thumbnails = isset( $_POST['thumbnails'] ) ? $_POST['thumbnails'] : null;

		// Check the nonce.
		if ( ! wp_verify_nonce( $nonce, 'regen' ) || ! \current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'error' => __( 'Trying to cheat ?', 'simple-image-sizes' ) ] );
		}

		// Get the id.
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		wp_send_json( Main::thumbnail_rebuild( $id, $thumbnails ) );
	}

	/**
	 * Add the thumbnail name in the post insertion, based on new WP filter
	 *
	 * @access public
	 *
	 * @param array $sizes : the sizes.
	 *
	 * @return array
	 * @since 2.3
	 * @author Nicolas Juen
	 * @author radeno based on this post : http://www.wpmayor.com/wordpress-hacks/how-to-add-custom-image-sizes-to-wordpress-uploader/
	 */
	public static function add_thumbnail_name( array $sizes ) {
		// Get options.
		$sizes_custom = get_option( SIS_OPTION, [] );

		// init size array.
		$add_sizes = [];

		// check there is custom sizes.
		if ( is_array( $sizes_custom ) && ! empty( $sizes_custom ) ) {
			foreach ( $sizes_custom as $key => $value ) {
				// If we show this size in the admin.
				if ( ! isset( $value['s'] ) || 1 !== (int) $value['s'] ) {
					continue;
				}
				$add_sizes[ $key ] = self::get_thumbnail_name( $key );
			}
		}

		// Merge the two array.
		$new_sizes = array_merge( $sizes, $add_sizes );

		// Add new size.
		return $new_sizes;
	}

	/**
	 * Get a thumbnail name from its slug
	 *
	 * @access private
	 *
	 * @param string $thumbnail_slug : the slug of the thumbnail.
	 *
	 * @return string
	 * @since 2.3
	 * @author Nicolas Juen
	 */
	private static function get_thumbnail_name( $thumbnail_slug = '' ) {

		// get the options.
		$sizes_custom = get_option( SIS_OPTION );

		if ( ! isset( $sizes_custom[ $thumbnail_slug ] ) ) {
			// return slug if not found.
			return $thumbnail_slug;
		}

		// If the name exists return it, slug by default.
		if ( isset( $sizes_custom[ $thumbnail_slug ]['n'] ) && ! empty( $sizes_custom[ $thumbnail_slug ]['n'] ) ) {
			return $sizes_custom[ $thumbnail_slug ]['n'];
		}

		return $thumbnail_slug;
	}


	/**
	 * Add action in media row
	 *
	 * @since 2.2
	 * @access public
	 *
	 * @param array    $actions : array of actions and content to display.
	 * @param \WP_Post $object : the WordPress object for the actions.
	 *
	 * @return array  $actions
	 * @author Nicolas Juen
	 */
	public static function add_actions_list( $actions, $object ) {
		if ( ! wp_attachment_is_image( $object->ID ) ) {
			return $actions;
		}
		// Add action for regeneration.
		$actions['sis-regenerate'] = sprintf( "<a href='#' data-id='%s' class='sis-regenerate-one'>%s</a>", esc_attr( $object->ID ), esc_html__( 'Regenerate thumbnails', 'simple-image-sizes' ) );

		// Return actions.
		return $actions;
	}


	/**
	 * Get a thumbnail name from its slug
	 *
	 * @access public
	 *
	 * @param array    $fields : the fields of the media.
	 * @param \WP_Post $post : the post object.
	 *
	 * @return array
	 * @since 2.3.1
	 * @author Nicolas Juen
	 */
	public static function add_field_regenerate( $fields, $post ) {
		// Check this is an image.
		if ( false === strpos( $post->post_mime_type, 'image' ) ) {
			return $fields;
		}

		$fields['sis-regenerate'] = [
			'label'         => __( 'Regenerate Thumbnails', 'simple-image-sizes' ),
			'input'         => 'html',
			'html'          => sprintf(
				'
			<input type="button" data-id="%s" class="button title sis-regenerate-one" value="%s" />
			<span class="spinner"></span>
			<span class="title"><em></em></span>
			<input type="hidden" class="regen" value="%s" />',
				esc_attr( $post->ID ),
				esc_attr__( 'Regenerate Thumbnails', 'simple-image-sizes' ),
				wp_create_nonce( 'regen' )
			),
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		return $fields;
	}
}
