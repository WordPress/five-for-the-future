<?php

/**
 * Track and display key metrics for the program, to measure growth and effectiveness.
 */

namespace WordPressDotOrg\FiveForTheFuture\Stats;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Contributor, Pledge, XProfile };
use WP_Query;

use function WordPressDotOrg\FiveForTheFuture\XProfile;

use const WordPressDotOrg\FiveForTheFuture\PREFIX;

defined( 'WPINC' ) || die();

const CPT_ID = PREFIX . '_stats_snapshot';

add_action( 'init',                      __NAMESPACE__ . '\register_post_types' );
add_action( 'init',                      __NAMESPACE__ . '\schedule_cron_jobs' );
add_action( PREFIX . '_record_snapshot', __NAMESPACE__ . '\record_snapshot' );

add_shortcode( PREFIX . '_stats', __NAMESPACE__ . '\render_shortcode' );


/**
 * Register the snapshots post type. Each post represents a snapshot of the stats at a point in time.
 */
function register_post_types() {
	$args = array(
		'supports'        => array( 'custom-fields' ),
		'public'          => false,
		'show_in_rest'    => true,

		// Only allow posts to be created programmatically.
		'capability_type' => CPT_ID,
		'capabilities'    => array(
			'create_posts' => 'do_not_allow',
		),
	);

	register_post_type( CPT_ID, $args );
}

/**
 * Schedule the cron job to record a stats snapshot.
 */
function schedule_cron_jobs() {
	if ( wp_next_scheduled( PREFIX . '_record_snapshot' ) ) {
		return;
	}

	// Schedule a repeating "single" event to avoid having to create a custom schedule.
	wp_schedule_single_event(
		time() + ( 2 * WEEK_IN_SECONDS ),
		PREFIX . '_record_snapshot'
	);
}

/**
 * Record a snapshot of the current stats, so we can track trends over time.
 */
function record_snapshot() {
	$stats = get_snapshot_data();

	$post_id = wp_insert_post( array(
		'post_type'   => CPT_ID,
		'post_author' => 0,
		'post_title'  => sprintf( '5ftF Stats Snapshot %s', date( 'Y-m-d' ) ),
		'post_status' => 'publish',
	) );

	// # of hours contributed by people who are sponsored by a registered company.
	add_post_meta( $post_id, PREFIX . '_total_pledged_hours',             $stats['confirmed_company_hours'] );
	// # of contributors sponsored by a registered company.
	add_post_meta( $post_id, PREFIX . '_total_pledged_contributors',      $stats['confirmed_company_contributors'] );
	// # of companies that are registered in the program.
	add_post_meta( $post_id, PREFIX . '_total_pledged_companies',         $stats['confirmed_companies'] );
	// # of company-sponsored contributors that each team has.
	add_post_meta( $post_id, PREFIX . '_total_pledged_team_contributors', $stats['confirmed_team_company_contributors'] );
}

/**
 * Calculate the stats for the current snapshot.
 *
 * @return array
 */
function get_snapshot_data() {
	$snapshot_data = array(
		'confirmed_company_hours'             => 0,
		'confirmed_team_company_contributors' => array(),
	);

	$confirmed_companies = new WP_Query( array(
		'post_type'   => Pledge\CPT_ID,
		'post_status' => 'publish',
		'numberposts' => 1, // We only need `found_posts`, not the posts themselves.
	) );

	$snapshot_data['confirmed_companies'] = $confirmed_companies->found_posts;

	/*
	 * A potential future optimization would be make WP_Query only return the `post_title`. The `fields` parameter
	 * doesn't currently support `post_title`, but it may be possible with filters like `posts_fields`
	 * or `posts_fields_request`. That was premature at the time this code was written, though.
	 */
	$confirmed_company_contributors = get_posts( array(
		'post_type'   => Contributor\CPT_ID,
		'post_status' => 'publish',
		'numberposts' => 2000,
	) );

	/*
	 * Removing duplicates because a user sponsored by multiple companies will have multiple contributor posts,
	 * but their stats should only be counted once.
	 *
	 * A potential future optimization would be to remove duplicate `post_title` entries in the query itself,
	 * but `WP_Query` doesn't support `DISTINCT` directly, and it's premature at this point. It may be possible
	 * with the filters mentioned above.
	 */
	$confirmed_user_ids                              = array_unique( Contributor\get_contributor_user_ids( $confirmed_company_contributors ) );
	$snapshot_data['confirmed_company_contributors'] = count( $confirmed_user_ids );
	$company_contributors_profile_data               = XProfile\get_xprofile_contribution_data( $confirmed_user_ids );

	foreach ( $company_contributors_profile_data as $profile_data ) {
		switch ( (int) $profile_data['field_id'] ) {
			case XProfile\FIELD_IDS['hours_per_week']:
				$snapshot_data['confirmed_company_hours'] += absint( $profile_data['value'] );
				break;

			case XProfile\FIELD_IDS['team_names']:
				/*
				 * BuddyPress validates the team name(s) the user provides before saving them in the database, so
				 * it should be safe to unserialize, and to assume that they're valid.
				 *
				 * The database stores team _names_ rather than _IDs_, though, so if a team is ever renamed, this
				 * data will be distorted.
				 */
				$associated_teams = (array) maybe_unserialize( $profile_data['value'] );

				foreach ( $associated_teams as $team ) {
					if ( isset( $snapshot_data['confirmed_team_company_contributors'][ $team ] ) ) {
						$snapshot_data['confirmed_team_company_contributors'][ $team ]++;
					} else {
						$snapshot_data['confirmed_team_company_contributors'][ $team ] = 1;
					}
				}

				break;
		}
	}

	return $snapshot_data;
}

/**
 * Render the shortcode to display stats.
 *
 * @return string
 */
function render_shortcode() {
	$snapshots = get_posts( array(
		'post_type'      => CPT_ID,
		'posts_per_page' => 500,
		'order'          => 'ASC',
	) );

	$stat_keys = array(
		PREFIX . '_total_pledged_hours',
		PREFIX . '_total_pledged_contributors',
		PREFIX . '_total_pledged_companies',
		PREFIX . '_total_pledged_team_contributors',

		// Deprecated because confusing and not meaningful, see https://github.com/WordPress/five-for-the-future/issues/198.
		PREFIX . '_total_sponsored_hours',
		PREFIX . '_total_sponsored_contributors',
	);

	$stat_values = array();

	// todo produce whatever data structure the visualization framework wants, and any a11y text fallback necessary.
		// don't trust that visualization library will escape things properly, run numbers through absint(), team names through sanitize_text_field(), etc.

	foreach ( $snapshots as $snapshot ) {
		$timestamp = strtotime( $snapshot->post_date );

		foreach ( $stat_keys as $stat_key ) {
			$stat_value                             = $snapshot->{ $stat_key };
			$stat_values[ $stat_key ][ $timestamp ] = $stat_value;
		}
	}

	ob_start();
	require FiveForTheFuture\get_views_path() . 'list-stats.php';
	return ob_get_clean();
}
