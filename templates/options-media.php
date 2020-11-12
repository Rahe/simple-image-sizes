<?php
// Get the sizes.
global $_wp_additional_image_sizes, $_wp_post_type_features;

use Rahe\Simple_Image_Sizes\Admin\Main; ?>
<input type="hidden" class="addSize" value='<?php echo esc_attr( wp_create_nonce( 'add_size' ) ); ?>'/>
<input type="hidden" class="regen" value='<?php echo esc_attr( wp_create_nonce( 'regen' ) ); ?>'/>
<input type="hidden" class="getList" value='<?php echo esc_attr( wp_create_nonce( 'getList' ) ); ?>'/>
<div id="sis-regen">
	<div class="wrapper" style="">
		<h4> <?php esc_html_e( 'Select which thumbnails you want to rebuild:', 'simple-image-sizes' ); ?> </h4>
		<table id="sis_sizes" class="widefat page fixed sis">
			<thead>
			<tr>
				<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input checked="checked" type="checkbox"></th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Size name', 'simple-image-sizes' ); ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Width', 'simple-image-sizes' ); ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Height', 'simple-image-sizes' ); ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Crop ?', 'simple-image-sizes' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			// Display the sizes in the array.
			foreach ( get_intermediate_image_sizes() as $ims ) :
				// Don't make or numeric sizes that appear.
				if ( is_int( $ims ) ) {
					continue;
				}

				// Set width.
				$width = isset( $_wp_additional_image_sizes[ $ims ]['width'] ) ? (int) $_wp_additional_image_sizes[ $ims ]['width'] : get_option( "{$ims}_size_w" );

				// Set height.
				$height = isset( $_wp_additional_image_sizes[ $ims ]['height'] ) ? (int) $_wp_additional_image_sizes[ $ims ]['height'] : get_option( "{$ims}_size_h" );

				// Set crop.
				$crop = isset( $_wp_additional_image_sizes[ $ims ]['crop'] ) ? $_wp_additional_image_sizes[ $ims ]['crop'] : get_option( "{$ims}_crop" );

				if ( is_numeric( $crop ) || is_bool( $crop ) || is_string( $crop ) ) {
					$crop = absint( $crop ) > 0 ? __( 'Yes', 'simple-image-sizes' ) : __( 'No', 'simple-image-sizes' );
				} else {
					$crop = Main::get_crop_position_label( implode( '_', $crop ) );
				}

				?>
				<tr id="sis-<?php echo esc_attr( $ims ); ?>">
					<th class="check-column">
						<input type="checkbox" class="thumbnails" id="<?php echo esc_attr( $ims ); ?>" name="thumbnails[]" checked="checked" value="<?php echo esc_attr( $ims ); ?>"/>
					</th>
					<th>
						<label for="<?php echo esc_attr( $ims ); ?>"><?php echo esc_html( $ims ); ?></label>
					</th>
					<th>
						<label for="<?php echo esc_attr( $ims ); ?>"><?php echo esc_html( $width ); ?> px</label>
					</th>
					<th>
						<label for="<?php echo esc_attr( $ims ); ?>"><?php echo esc_html( $height ); ?> px</label>
					</th>
					<th>
						<label for="<?php echo esc_attr( $ims ); ?>"><?php echo esc_html( $crop ); ?> </label>
					</th>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input checked="checked" type="checkbox">
				</th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Size name', 'simple-image-sizes' ); ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Width', 'simple-image-sizes' ); ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Height', 'simple-image-sizes' ); ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Crop ?', 'simple-image-sizes' ); ?></th>
			</tr>
			</tfoot>
		</table>

		<h4><?php esc_html_e( 'Select which post type source thumbnails you want to rebuild:', 'simple-image-sizes' ); ?></h4>
		<table class="widefat page fixed sis">
			<thead>
			<tr>
				<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input checked="checked" type="checkbox">
				</th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Post type', 'simple-image-sizes' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			// Display the post types table.
			foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $ptype ) :
				// Avoid the post_types without post thumbnails feature.
				if ( ! isset( $_wp_post_type_features[ $ptype->name ] ) || ! array_key_exists( 'thumbnail', $_wp_post_type_features[ $ptype->name ] ) || false === $_wp_post_type_features[ $ptype->name ] ) {
					continue;
				}
				?>
				<tr>
					<th class="check-column">
						<label for="<?php echo esc_attr( $ptype->name ); ?>">
							<input type="checkbox" class="post_types" name="post_types[]" checked="checked" id="<?php echo esc_attr( $ptype->name ); ?>" value="<?php echo esc_attr( $ptype->name ); ?>"/>
						</label>
					</th>
					<th>
						<label
							for="<?php echo esc_attr( $ptype->name ); ?>"><em><?php echo esc_html( $ptype->labels->name ); ?></em></label>
					</th>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<th scope="col" id="cb" class="manage-column column-cb check-column"><input checked="checked" type="checkbox"></th>
				<th class="manage-column" scope="col"><?php esc_html_e( 'Post type', 'simple-image-sizes' ); ?></th>
			</tr>
			</tfoot>
		</table>
	</div>
</div>
<div class="sis">
	<div class="regenerate_message"></div>
	<div class="media-item sis">
		<div class="progress">
			<div id="sis_progress-percent" class="percent">100%</div>
			<div class="bar"></div>
		</div>
	</div>

	<div class="ui-widget time">
		<div class="ui-state-highlight ui-corner-all">
			<p>
				<span class="ui-icon ui-icon-info"></span>
				<span><strong><?php esc_html_e( 'End time calculated :', 'simple-image-sizes' ); ?></strong> <span class='time_message'><?php esc_html_e( 'Calculating...', 'simple-image-sizes' ); ?></span> </span>
			</p>
			<ul class="messages"></ul>
		</div>
	</div>
	<div id="error_messages">
		<p>
		<ol class="messages">
		</ol>
		</p>
	</div>
	<div class="thumb"><h4><?php esc_html_e( 'Last image:', 'simple-image-sizes' ); ?></h4><img class="thumb-img"/></div>
	<input type="button" class="button" name="ajax_thumbnail_rebuild" id="ajax_thumbnail_rebuild" value="<?php esc_attr_e( 'Regenerate Thumbnails', 'simple-image-sizes' ); ?>"/>
</div>
