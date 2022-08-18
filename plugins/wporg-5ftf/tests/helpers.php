<?php

namespace WordPressDotOrg\FiveForTheFuture\Tests\Helpers;
use WordPressDotOrg\FiveForTheFuture\{ Pledge };
use WP_UnitTest_Factory;

/**
 * Sets up the database before a test class is loaded.
 *
 * Call in `set_up_before_class()`.
 */
function database_setup_before_class( WP_UnitTest_Factory $factory ) : array {
	global $wpdb;

	$fixtures = array();

	$wpdb->query( "
		CREATE TABLE `bpmain_bp_xprofile_data` (
			`id` bigint unsigned NOT NULL AUTO_INCREMENT,
			`field_id` bigint unsigned NOT NULL DEFAULT '0',
			`user_id` bigint unsigned NOT NULL DEFAULT '0',
			`value` longtext NOT NULL,
			`last_updated` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			PRIMARY KEY (`id`),
			KEY `field_id` (`field_id`),
			KEY `user_id` (`user_id`)
		)
		ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3
	" );

	// Users
	$fixtures['users']['jane'] = $factory->user->create_and_get( array(
		'user_login' => 'jane',
		'user_email' => 'jane@example.org',
	) );

	$fixtures['users']['ashish'] = $factory->user->create_and_get( array(
		'user_login' => 'ashish',
		'user_email' => 'ashish@example.org',
	) );

	// Pages
	$for_organizations = $factory->post->create_and_get( array(
		'post_type'   => 'page',
		'post_title'  => 'For Organizations',
		'post_status' => 'publish',
	) );
	$fixtures['pages']['for_organizations'] = $for_organizations;
	$GLOBALS['post'] = $for_organizations; // `create_new_pledge()` assumes this exists.

	// Pledges
	$tenup_id    = Pledge\create_new_pledge( '10up' );
	$bluehost_id = Pledge\create_new_pledge( 'BlueHost' );

	wp_update_post( array(
		'ID'          => $tenup_id,
		'post_status' => 'publish',
	) );
	wp_update_post( array(
		'ID'          => $bluehost_id,
		'post_status' => 'publish',
	) );

	$fixtures['pledges']['10up']     = get_post( $tenup_id );
	$fixtures['pledges']['bluehost'] = get_post( $bluehost_id );

	return $fixtures;
}

/**
 * Sets up the database before a test is ran.
 *
 * Call in `set_up()`.
 */
function database_set_up( int $jane_id, int $ashish_id ) : void {
	global $wpdb;

	$wpdb->query( 'TRUNCATE TABLE `bpmain_bp_xprofile_data` ' );

	$wpdb->query( $wpdb->prepare( "
		INSERT INTO `bpmain_bp_xprofile_data`
		(`id`, `field_id`, `user_id`, `value`, `last_updated`)
		VALUES
			(NULL, 29, %d, '40', '2019-12-02 10:00:00' ),
			(NULL, 30, %d, 'a:1:{i:0;s:9:\"Core Team\";}', '2019-12-03 11:00:00' ),
			(NULL, 29, %d, '35', '2019-12-02 10:00:00' ),
			(NULL, 30, %d, 'a:1:{i:0;s:18:\"Documentation Team\";}', '2019-12-03 11:00:00' )",
		$jane_id,
		$jane_id,
		$ashish_id,
		$ashish_id
	) );
}

/**
 * Tears down the database after all tests in a class have ran.
 *
 * Call in `tear_down_after_class()`.
 */
function database_tear_down_after_class() : void {
	global $wpdb;

	$wpdb->query( 'DROP TABLE `bpmain_bp_xprofile_data` ' );
}
