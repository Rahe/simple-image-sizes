<?php
/*
Plugin Name: Simple Image Sizes
Plugin URI: https://github.com/Rahe/simple-image-sizes
Description: Add options in media setting page for images sizes
Version: 3.2.3
Author: Rahe
Author URI: http://nicolas-juen.fr
Text Domain: simple-image-sizes
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 8.0

Copyright 2011 Nicolas JUEN (njuen@beapi.fr) - Be-API

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

use Rahe\Simple_Image_Sizes\Admin\Main;
use Rahe\Simple_Image_Sizes\Admin\Media;
use Rahe\Simple_Image_Sizes\Admin\Post;

define( 'SIS_URL', plugin_dir_url( __FILE__ ) );
define( 'SIS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIS_VERSION', '3.2.3' );
define( 'SIS_OPTION', 'custom_image_sizes' );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

add_action( 'plugins_loaded', 'init_sis' );
function init_sis() {
	new Rahe\Simple_Image_Sizes\Main();

	if ( is_admin() ) {
		new Main();
		new Post();
		new Media();
	}
}
