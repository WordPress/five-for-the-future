<?php
namespace WordPressDotOrg\FiveForTheFuture\XProfile;

use WordPressDotOrg\FiveForTheFuture\Contributor;
use wpdb;

/*
 * The IDs of the xprofile fields we need. Better to use the numerical IDs than the field labels,
 * because those are more likely to change.
 */
const FIELD_IDS = array(
	'hours_per_week' => 29,
	'team_names'     => 30,
);

defined( 'WPINC' ) || die();

/**
 * Pull relevant data from profiles.wordpress.org.
 *
 * Note that this does not unserialize anything, it just pulls the raw values from the database table.
 *
 * @global wpdb $wpdb
 *
 * @param array $user_ids
 *
 * @return array
 */
function get_xprofile_contribution_data( array $user_ids ) {
	global $wpdb;

	$sql = $wpdb->prepare(
		'
			SELECT user_id, field_id, value
			FROM bpmain_bp_xprofile_data
			WHERE user_id IN ( %1$s )
			AND field_id IN ( %2$s )
		',
		implode( ', ', array_map( 'absint', $user_ids ) ),
		implode( ', ', array_map( 'absint', array_values( FIELD_IDS ) ) )
	);

	return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL -- prepare called above.
}

/**
 * Aggregate the raw xprofile data for all contributors linked to a given pledge.
 *
 * @param int $pledge_id
 *
 * @return array|false
 */
function get_aggregate_contributor_data_for_pledge( $pledge_id ) {
	$contributor_posts = Contributor\get_pledge_contributors( $pledge_id, 'publish' );

	// All of their contributors might have declined the invitation and had their posts deleted.
	if ( ! $contributor_posts ) {
		return false;
	}

	$contributor_users = Contributor\get_contributor_user_objects( $contributor_posts );
	$user_ids          = wp_list_pluck( $contributor_users, 'ID' );

	$data = get_xprofile_contribution_data( $user_ids );

	$initial = array(
		'contributors' => count( $user_ids ),
		'hours'        => 0,
		'teams'        => array(),
	);

	$aggregate_data = array_reduce( $data, function( $carry, $item ) {
		switch ( $item['field_id'] ) {
			case 29: // Hours.
				$carry['hours'] += absint( $item['value'] );
				break;

			case 30: // Teams.
				$value          = maybe_unserialize( $item['value'] );
				$carry['teams'] = array_merge( $carry['teams'], $value );
				break;
		}

		return $carry;
	}, $initial );

	$aggregate_data['teams'] = array_unique( $aggregate_data['teams'] );
	sort( $aggregate_data['teams'] );

	return $aggregate_data;
}

/**
 * Fetch the profile data for a specific user.
 *
 * @param int $user_id
 *
 * @return array
 */
function get_contributor_user_data( $user_id ) {
	$formatted_data = array();
	$raw_data       = get_xprofile_contribution_data( array( $user_id ) );

	$defaults = array(
		'hours_per_week' => 0,
		'team_names'     => array(),
	);

	foreach ( $raw_data as $datum ) {
		$key = array_search( $datum['field_id'], FIELD_IDS );

		switch ( $key ) {
			case 'hours_per_week':
				$formatted_data[ $key ] = absint( $datum['value'] );
				break;

			case 'team_names':
				$formatted_data[ $key ] = maybe_unserialize( $datum['value'] );
		}
	}

	$formatted_data = array_merge( $defaults, $formatted_data );

	return $formatted_data;
}
