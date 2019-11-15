<?php
/**
 * Plugin Name:     Five For The Future
 * Plugin URI:      https://wordpress.org/five-for-the-future/
 * Description:
 * Author:          WordPress.org
 * Author URI:      https://wordpress.org
 * Version:         1.0.0
 */

namespace WordPressDotOrg\FiveForTheFuture;

defined( 'WPINC' ) || die();

define( __NAMESPACE__ . '\PATH', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

const PREFIX = '5ftf';

add_action( 'plugins_loaded', __NAMESPACE__ . '\load' );

/**
 * Include the rest of the plugin.
 */
function load() {
	$running_unit_tests = isset( $_SERVER['_'] ) && false !== strpos( $_SERVER['_'], 'phpunit' );

	require_once get_includes_path() . 'authentication.php';
	require_once get_includes_path() . 'contributor.php';
	require_once get_includes_path() . 'email.php';
	require_once get_includes_path() . 'pledge.php';
	require_once get_includes_path() . 'pledge-meta.php';
	require_once get_includes_path() . 'pledge-form.php';
	require_once get_includes_path() . 'xprofile.php';
	require_once get_includes_path() . 'miscellaneous.php';

	// The logger expects things like `$_POST` which aren't set during unit tests.
	if ( ! $running_unit_tests ) {
		require_once get_includes_path() . 'pledge-log.php';
	}
}

/**
 * Shortcut to the assets directory.
 *
 * @return string
 */
function get_assets_path() {
	return PATH . 'assets/';
}

/**
 * Shortcut to the assets URL.
 *
 * @return string
 */
function get_assets_url() {
	return URL . 'assets/';
}

/**
 * Shortcut to the includes directory.
 *
 * @return string
 */
function get_includes_path() {
	return PATH . 'includes/';
}

/**
 * Shortcut to the views directory.
 *
 * @return string
 */
function get_views_path() {
	return PATH . 'views/';
}
