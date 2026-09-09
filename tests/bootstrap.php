<?php
/**
 * PHPUnit bootstrap for the plugin.
 */

$plugin_root = dirname( __DIR__ );
$polyfills_autoload = $plugin_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

if ( file_exists( $polyfills_autoload ) ) {
    require_once $polyfills_autoload;
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    throw new RuntimeException( "Unable to locate WordPress PHPUnit test suite in {$_tests_dir}. Run `wp-env start` first." );
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
    'muplugins_loaded',
    static function (): void {
        require dirname( __DIR__ ) . '/em-rest-api-cpt.php';
    }
);

require_once $_tests_dir . '/includes/bootstrap.php';
