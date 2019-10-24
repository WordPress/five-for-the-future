<?php
namespace WordPressDotOrg\FiveForTheFuture\XProfile;

use WordPressDotOrg\FiveForTheFuture\Contributor;
use wpdb;

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

	// The IDs of the xprofile fields we need. Better to use the numerical IDs than the field labels,
	// because those are more likely to change.
	$field_ids = array(
		29, // Hours per week.
		30, // Teams, the value of this field is serialized in the database.
	);

	$sql = $wpdb->prepare(
		'
			SELECT user_id, field_id, value
			FROM bpmain_bp_xprofile_data
			WHERE user_id IN ( %1$s )
			AND field_id IN ( %2$s )
		',
		implode( ', ', array_map( 'absint', $user_ids ) ),
		implode( ', ', array_map( 'absint', $field_ids ) )
	);

	return $wpdb->get_results( $sql, ARRAY_A );
}

/**
 * Aggregate the raw xprofile data for all contributors linked to a given pledge.
 *
 * @param int $pledge_id
 *
 * @return array
 */
function get_aggregate_contributor_data_for_pledge( $pledge_id ) {
	$contributors = Contributor\get_contributor_user_objects(
		Contributor\get_pledge_contributors( $pledge_id, 'pending' ) // TODO set to 'publish' when finished testing
	);
	$user_ids     = wp_list_pluck( $contributors, 'ID' );

	$data = get_xprofile_contribution_data( $user_ids );

	$initial = array(
		'contributors' => count( $user_ids ),
		'hours'        => 0,
		'teams'        => array(),
	);

	$aggregate_data = array_reduce( $data, function( $carry, $item ) {
		switch( $item['field_id'] ) {
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
