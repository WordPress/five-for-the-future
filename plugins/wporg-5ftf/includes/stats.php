<?php

/**
 * Track and display key metrics for the program, to measure growth and effectiveness.
 */

namespace WordPressDotOrg\FiveForTheFuture\Stats;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Contributor, Pledge, XProfile };
use WP_Query;

defined( 'WPINC' ) || die();

add_action( 'init',                 __NAMESPACE__ . '\register_post_types' );
add_action( 'init',                 __NAMESPACE__ . '\schedule_cron_jobs' );
add_action( '5ftf_record_snapshot', __NAMESPACE__ . '\record_snapshot' );

add_shortcode( '5ftf_stats', __NAMESPACE__ . '\render_shortcode' );


/**
 * Register the snapshots post type. Each post represents a snapshot of the stats at a point in time.
 */
function register_post_types() {
	$args = array(
		'supports'     => array( 'custom-fields' ),
		'public'       => false,
		'show_in_rest' => true,

		// Only allow posts to be created programmatically.
		'capability_type' => '5ftf_stats',
		'capabilities'    => array(
			'create_posts' => 'do_not_allow',
		),
	);

	register_post_type( '5ftf_stats_snapshot', $args );
}

/**
 * Schedule the cron job to record a stats snapshot.
 */
function schedule_cron_jobs() {
	if ( wp_next_scheduled( '5ftf_record_snapshot' ) ) {
		return;
	}

	// Schedule a repeating "single" event to avoid having to create a custom schedule.
	wp_schedule_single_event(
		time() + ( 2 * WEEK_IN_SECONDS ),
		'5ftf_record_snapshot'
	);
}

/**
 * Record a snapshot of the current stats, so we can track trends over time.
 */
function record_snapshot() {
	$stats = get_snapshot_data();

	$post_id = wp_insert_post( array(
		'post_type'   => '5ftf_stats_snapshot',
		'post_author' => 0,
		'post_title'  => sprintf( '5ftF Stats Snapshot %s', date( 'Y-m-d' ) ),
		'post_status' => 'publish',
	) );

	add_post_meta( $post_id, '5ftf_total_pledged_hours',             $stats['confirmed_hours'] );
	add_post_meta( $post_id, '5ftf_total_pledged_contributors',      $stats['confirmed_contributors'] );
	add_post_meta( $post_id, '5ftf_total_pledged_companies',         $stats['confirmed_pledges'] );
	add_post_meta( $post_id, '5ftf_total_pledged_team_contributors', $stats['confirmed_team_contributors'] );
}

/**
 * Calculate the stats for the current snapshot.
 *
 * @return array
 */
function get_snapshot_data() {
	$snapshot_data = array(
		'confirmed_hours'             => 0,
		'confirmed_team_contributors' => array(),
	);

	$confirmed_pledges = new WP_Query( array(
		'post_type'   => Pledge\CPT_ID,
		'post_status' => 'publish',
		'numberposts' => 1, // We only need `found_posts`, not the posts themselves.
	) );

	$snapshot_data['confirmed_pledges'] = $confirmed_pledges->found_posts;

	/*
	 * A potential future optimization would be make WP_Query only return the `post_title`. The `fields` parameter
	 * doesn't currently support `post_title`, but it may be possible with filters like `posts_fields`
	 * or `posts_fields_request`. That was premature at the time this code was written, though.
	 */
	$confirmed_contributors = get_posts( array(
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
	$confirmed_user_ids                      = array_unique( Contributor\get_contributor_user_ids( $confirmed_contributors ) );
	$snapshot_data['confirmed_contributors'] = count( $confirmed_user_ids );

	$contributors_profile_data = XProfile\get_xprofile_contribution_data( $confirmed_user_ids );

	foreach ( $contributors_profile_data as $profile_data ) {
		switch ( (int) $profile_data['field_id'] ) {
			case XProfile\FIELD_IDS['hours_per_week']:
				$snapshot_data['confirmed_hours'] += absint( $profile_data['value'] );
				break;

			case XProfile\FIELD_IDS['team_names']:
				/*
				 * BuddyPress validates the team name(s) the user provides before saving them in the database, so
				 * it should be safe to unserialize, and to assume that they're valid.
				 *
				 * The database stores team _names_ rather than _IDs_, though, so if a team is ever renamed, this
				 * data will be distorted.
				 */
				$associated_teams = maybe_unserialize( $profile_data['value'] );

				foreach ( $associated_teams as $team ) {
					if ( isset( $snapshot_data['confirmed_team_contributors'][ $team ] ) ) {
						$snapshot_data['confirmed_team_contributors'][ $team ]++;
					} else {
						$snapshot_data['confirmed_team_contributors'][ $team ] = 1;
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
		'post_type'      => '5ftf_stats_snapshot',
		'posts_per_page' => 500,
		'order'          => 'ASC',
	) );

	$stat_keys = array(
		'5ftf_total_pledged_hours',
		'5ftf_total_pledged_contributors',
		'5ftf_total_pledged_companies',
		'5ftf_total_pledged_team_contributors',
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
