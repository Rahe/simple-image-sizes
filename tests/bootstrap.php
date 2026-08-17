<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Simple_Image_Sizes
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php." . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter().
require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin being tested.
 *
 * @return void
 */
function _manually_load_simple_image_sizes() {
	require dirname( __DIR__ ) . '/simple_image_sizes.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_simple_image_sizes' );

// Start the WordPress testing environment.
require $_tests_dir . '/includes/bootstrap.php';
