<?php
/**
 * Plugin Name:     Five For The Future
 * Plugin URI:      https://wordpress.org
 * Description:
 * Author:          WordPress.org
 * Author URI:      https://wordpress.org
 * Version:         1.0.0
 */

namespace WordPressDotOrg\FiveForTheFuture;
defined( 'WPINC' ) || die();

define( __NAMESPACE__ . '\PATH', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

/**
 *
 */
function load() {
	require_once PATH . 'includes/company.php';
	require_once PATH . 'includes/company-meta.php';
	require_once PATH . 'includes/shortcodes.php';
	require_once PATH . 'includes/pledge-form.php';
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\load' );
