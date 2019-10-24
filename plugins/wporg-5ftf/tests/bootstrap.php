<?php

namespace WordPressDotOrg\FiveForTheFuture\Tests;

if ( 'cli' !== php_sapi_name() ) {
	return;
}


define( 'WP_PLUGIN_DIR', dirname( dirname( __DIR__ ) ) );


$core_tests_directory = getenv( 'WP_TESTS_DIR' );

if ( ! $core_tests_directory ) {
	echo "\nPlease set the WP_TESTS_DIR environment variable to the folder where WordPress' PHPUnit tests live --";
	echo "\ne.g., export WP_TESTS_DIR=/srv/www/wordpress-develop/tests/phpunit\n";

	return;
}

require_once $core_tests_directory . '/includes/functions.php';
require_once dirname( dirname( $core_tests_directory ) ) . '/build/wp-admin/includes/plugin.php';

tests_add_filter( 'muplugins_loaded', function() {
	require_once dirname( __DIR__ )  . '/index.php';
} );

require_once $core_tests_directory . '/includes/bootstrap.php';
