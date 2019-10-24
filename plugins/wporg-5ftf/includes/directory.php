<?php

/**
 * This file handles operations related to registering blocks and their assets. If there end up being more than one
 * or two, we may want to create a subfolder and have a separate file for each block.
 */

// TODO are we actually using any of this?

namespace WordPressDotOrg\FiveForTheFuture\Pledge_Directory;
use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\Pledge;

defined( 'WPINC' ) || die();

/**
 * Enqueue scripts and styles.
 */
function enqueue_scripts() {
	global $post;

	wp_register_script(
		'5ftf-list',
		plugins_url( 'assets/js/front-end.js', __DIR__ ),
		array( 'jquery', 'underscore', 'wp-util' ),
		filemtime( FiveForTheFuture\PATH . '/assets/js/front-end.js' ),
		true
	);

	wp_register_style(
		'5ftf-front-end',
		plugins_url( 'assets/css/front-end.css', __DIR__ ),
		array( 'dashicons' ),
		filemtime( FiveForTheFuture\PATH . '/assets/css/front-end.css' )
	);

	if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'five_for_the_future_companies' ) ) {
		return;
	}

	$params = array(
		/*
		 * todo explain 100 is just sanity limit to keep page size performant. might need to lazy-load more in the
		 * future.
		 * maybe order by donated_employees, or rand, to ensure the top companies are always displayed first, or
		 * to make sure treat everyone equal.
		 */
		'post_type'      => Pledge\CPT_ID,
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$companies = get_posts( $params );

	foreach ( $companies as $key => $company ) {
		$teams = get_post_meta( $company->ID, '_5ftf_teams', false );

		$companies[ $key ] = array(
			'name'                  => $company->post_title,
			'url'                   => $company->_5ftf_url,
			'total_employees'       => $company->_5ftf_total_employees,
			'sponsored_employees'   => $company->_5ftf_sponsored_employees,
			'hours_per_week'        => $company->_5ftf_hours_per_week,
			'teams_contributing_to' => implode( ', ', $teams ),
		);
	}

	$inline_script = sprintf(
		'var fiveFutureCompanies = JSON.parse( decodeURIComponent( \'%s\' ) );',
		rawurlencode( wp_json_encode( $companies ) )
	);

	wp_enqueue_style( '5ftf-front-end' );
	wp_enqueue_script( '5ftf-list' );
	wp_add_inline_script( '5ftf-list', $inline_script );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );

/**
 * Todo.
 *
 * @return string
 */
function render_shortcode() {
	// The limit is just a sanity check, but ideally all should be displayed.
	// If this is reached, then refactor the page to lazy-load, etc.

	ob_start();
	require_once dirname( __DIR__ ) . '/views/front-end.php';
	return ob_get_clean();
}

add_shortcode( 'five_for_the_future_companies', __NAMESPACE__ . '\render_shortcode' );

// todo shortcode for pledge form.
// todo form handler for pledge form.

/**
 * Todo.
 */
function register() {
	//register_block_type();
}

add_action( 'init', __NAMESPACE__ . '\register' );
