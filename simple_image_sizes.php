<?php
/*
Plugin Name: Simple Image Sizes
Plugin URI: https://github.com/Rahe/Simple-image-sizes
Description: Add options in media setting page for images sizes
Version: 2.4.3
Author: Rahe
Author URI: http://nicolas-juen.fr
Text Domain: sis
Domain Path: /languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

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

define( 'SIS_URL', plugins_url('', __FILE__) );
define( 'SIS_DIR', dirname(__FILE__) );
define( 'SIS_VERSION', '2.4.3' );
define( 'SIS_OPTION', 'custom_image_sizes' );

require_once( SIS_DIR . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'class.admin.php'  );
require_once( SIS_DIR . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'class.client.php'  );

add_action ( 'plugins_loaded', 'initSIS' );
function initSIS() {
	global $SIS;
	if( is_admin() ) {
		$SIS['admin'] = new SISAdmin();
	}
	
	$SIS['client'] = new SISClient();
	
	load_plugin_textdomain ( 'sis', false, basename( rtrim( SIS_DIR, '/' ) ) . '/languages' );
} 