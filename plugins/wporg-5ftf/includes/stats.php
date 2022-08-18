<?php

/**
 * Track and display key metrics for the program, to measure growth and effectiveness.
 */

namespace WordPressDotOrg\FiveForTheFuture\Stats;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Contributor, Pledge, XProfile };
use WP_Query;

use const WordPressDotOrg\FiveForTheFuture\PREFIX;

defined( 'WPINC' ) || die();

const CPT_ID = PREFIX . '_stats_snapshot'; // Deprecated, new stats are in MC.

add_action( 'init',                      __NAMESPACE__ . '\register_post_types' );
add_action( 'init',                      __NAMESPACE__ . '\schedule_cron_jobs' );
add_action( PREFIX . '_record_snapshot', __NAMESPACE__ . '\record_snapshot' );

add_shortcode( PREFIX . '_stats', __NAMESPACE__ . '\render_shortcode' );


/**
 * Register the snapshots post type. Each post represents a snapshot of the stats at a point in time.
 *
 * @deprecated Stats were originally kept in these posts, but are currently stored in MC. This is kept so that we
 * have a historical record.
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

	wp_schedule_event( time(), 'daily', PREFIX . '_record_snapshot' );
}

/**
 * Record a snapshot of the current stats, so we can track trends over time.
 *
 * "Self-sponsored" contributors are those that volunteer their time rather than being paid by a company.
 */
function record_snapshot() {
	$stats = get_snapshot_data();

	bump_stats_extra( 'five-for-the-future', 'Self-sponsored hours', $stats['self_sponsored_hours'] );
	bump_stats_extra( 'five-for-the-future', 'Self-sponsored contributors', $stats['self_sponsored_contributors'] );
	bump_stats_extra( 'five-for-the-future', 'Self-sponsored contributor activity %', $stats['self_sponsored_contributor_activity'] );
	bump_stats_extra( 'five-for-the-future', 'Companies', $stats['companies'] );
	bump_stats_extra( 'five-for-the-future', 'Company-sponsored hours', $stats['company_sponsored_hours'] );
	bump_stats_extra( 'five-for-the-future', 'Company-sponsored contributors', $stats['company_sponsored_contributors'] );
	bump_stats_extra( 'five-for-the-future', 'Company-sponsored contributor activity %', $stats['company_sponsored_contributor_activity'] );

	foreach ( array( 'team_company_sponsored_contributors', 'team_self_sponsored_contributors' ) as $key ) {
		foreach ( $stats[ $key ] as $team => $contributors ) {
			// The labels are listed alphabetically in MC, so starting them all with "Team" groups them together and
			// makes the interface easier to use.
			$grouped_name = sprintf(
				'Team %s %s-sponsored contributors',
				str_replace( ' Team', '', $team ),
				str_contains( $key, 'self' ) ? 'self' : 'company'
			);

			bump_stats_extra( 'five-for-the-future', $grouped_name, $contributors );
		}
	}
}

/**
 * Calculate the stats for the current snapshot.
 *
 * This will be processing a large amount of data, so `unset()` is used throughout the function on variables that
 * are no longer needed. That should help to avoid out-of-memory errors.
 *
 * @return array
 */
function get_snapshot_data() {
	$active_self_sponsored_contributors    = 0;
	$active_company_sponsored_contributors = 0;

	$snapshot_data = array(
		'company_sponsored_hours'             => 0,
		'self_sponsored_hours'                => 0,
		'team_company_sponsored_contributors' => array(),
		'team_self_sponsored_contributors'    => array(),
	);

	$companies = new WP_Query( array(
		'post_type'   => Pledge\CPT_ID,
		'post_status' => 'publish',
		'numberposts' => 1, // We only need `found_posts`, not the posts themselves.
	) );

	$snapshot_data['companies'] = $companies->found_posts;
	unset( $companies );

	/*
	 * A potential future optimization would be make WP_Query only return the `post_title`. The `fields` parameter
	 * doesn't currently support `post_title`, but it may be possible with filters like `posts_fields`
	 * or `posts_fields_request`. That was premature at the time this code was written, though.
	 */
	$company_sponsored_contributors = get_posts( array(
		'post_type'   => Contributor\CPT_ID,
		'post_status' => 'publish',
		'numberposts' => -1,
	) );

	/*
	 * Removing duplicates because a user sponsored by multiple companies will have multiple contributor posts,
	 * but their stats should only be counted once.
	 *
	 * A potential future optimization would be to remove duplicate `post_title` entries in the query itself,
	 * but `WP_Query` doesn't support `DISTINCT` directly, and it's premature at this point. It may be possible
	 * with the filters mentioned above.
	 */
	$company_contributor_user_ids = array_unique( Contributor\get_contributor_user_ids( $company_sponsored_contributors ) );
	unset( $company_sponsored_contributors );

	$all_contributor_profiles                        = XProfile\get_all_xprofile_contributor_hours_teams();
	$snapshot_data['company_sponsored_contributors'] = count( $company_contributor_user_ids );
	$snapshot_data['self_sponsored_contributors']    = count( $all_contributor_profiles ) - count( $company_contributor_user_ids );
	$full_users                                      = Contributor\add_user_data_to_xprofile( $all_contributor_profiles );
	unset( $all_contributor_profiles );

	foreach ( $full_users as $user ) {
		$is_company_sponsored = in_array( $user['user_id'], $company_contributor_user_ids, true );
		$attribution_prefix   = $is_company_sponsored ? 'company_sponsored' : 'self_sponsored';

		if ( Contributor\is_active( $user['last_logged_in'] ) ) {
			if ( $is_company_sponsored ) {
				$active_company_sponsored_contributors++;
			} else {
				$active_self_sponsored_contributors++;
			}
		}

		$team_contributor_key = sprintf( 'team_%s_contributors', $attribution_prefix );

		$snapshot_data[ $attribution_prefix . '_hours'] += $user['hours_per_week'];

		foreach ( $user['team_names'] as $team ) {
			if ( isset( $snapshot_data[ $team_contributor_key ][ $team ] ) ) {
				$snapshot_data[ $team_contributor_key ][ $team ] ++;
			} else {
				$snapshot_data[ $team_contributor_key ][ $team ] = 1;
			}
		}
	}
	unset( $all_contributor_profiles );

	// Alphabetize so that they appear in a consistent order in the MC interface.
	ksort( $snapshot_data['team_company_sponsored_contributors'] );
	ksort( $snapshot_data['team_self_sponsored_contributors'] );

	$snapshot_data['self_sponsored_contributor_activity']    = round( $active_self_sponsored_contributors / $snapshot_data['self_sponsored_contributors'] * 100, 2 );
	$snapshot_data['company_sponsored_contributor_activity'] = round( $active_company_sponsored_contributors / $snapshot_data['company_sponsored_contributors'] * 100, 2 );

	return $snapshot_data;
}

/**
 * Render the shortcode to display stats.
 *
 * @deprecated Stats were originally kept in these posts, but are currently stored in MC. This is kept so that we
 * have a historical record.
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
