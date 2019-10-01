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
 *
 */
function load() {
	require_once PATH . 'includes/pledge.php';
	require_once PATH . 'includes/pledge-meta.php';
	require_once PATH . 'includes/pledge-form.php';
	require_once PATH . 'includes/shortcodes.php';
}
