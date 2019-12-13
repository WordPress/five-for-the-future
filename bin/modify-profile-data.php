<?php

/*
 * Modify existing `bpmain_bp_xprofile_data` fields to match new team names, etc.
 *
 * Usage: wp eval-file modify-profile-data.php
 *
 * @see https://github.com/WordPress/five-for-the-future/issues/83
 */

namespace WordPressDotOrg\FiveForTheFuture\Bin;
use WordPressDotOrg\FiveForTheFuture\XProfile;
use WP_ClI;

wp_debug_mode();    // re-set `display_errors` after WP-CLI overrides it, see https://github.com/wp-cli/wp-cli/issues/706#issuecomment-203610437

/** @var string $file The filename of the current script */
/** @var array  $args The arguments passed to this script from the command line */
main( $file, $args );


/**
 * The main controller
 */
function main( $file, $args ) {
	WP_CLI::line();

	WP_CLI::confirm(
		"Be very careful when using this, as it will modify production data. It should only be run manually after careful review of the code. When adding new code, try to make it idempotent so this can be safely re-run. Take a backup of the db table before running this, in case anything goes wrong. Proceed?"
	);

	update_chosen_teams();
	update_inaccurate_hours();

	WP_CLI::success( 'Done. Please manually check everything to make sure it worked correctly.' );
}

/**
 * Make changes to the data the users have saved about their teams.
 */
function update_chosen_teams() {
	global $wpdb;

	$table = 'bpmain_bp_xprofile_data';

	$query = $wpdb->prepare( "
		SELECT id, value
		FROM `$table`
		WHERE field_id = %d",
		XProfile\FIELD_IDS['team_names']
	);

	$rows = $wpdb->get_results( $query, ARRAY_A );

	foreach ( $rows as $row ) {
		$updated      = false;
		$chosen_teams = (array) maybe_unserialize( $row['value'] );

		if ( empty( $chosen_teams ) ) {
			continue;
		}

		$original_team_count = count( $chosen_teams ); // Can't do this in the loop because it would be reduced when team are removed.

		for ( $i = 0; $i < $original_team_count; $i++ ) {
			/*
			 * Postfix 'Team' to all team names -- e.g., 'Support' => 'Support Team' -- to make it obvious that
			 * we're referring to an official Make team, not supporting a private plugin/theme.
			 */
			if ( 'Team' !== substr( $chosen_teams[ $i ], -4 ) ) {
				$updated             = true;
				$chosen_teams[ $i ] .= ' Team';
			}

			// Clarify name of Theme Review Team to remove ambiguity.
			if ( 'Themes Team' === $chosen_teams[ $i ] ) {
				$updated            = true;
				$chosen_teams[ $i ] = 'Theme Review Team';
			}

			// Clarify name of WP-CLI Team to remove ambiguity.
			if ( 'CLI Team' === $chosen_teams[ $i ] ) {
				$updated            = true;
				$chosen_teams[ $i ] = 'WP-CLI Team';
			}

			// Remove users from closed groups, because the vast majority of them are not actually members.
			if ( 'Plugins Team' === $chosen_teams[ $i ] || 'Security Team' === $chosen_teams[ $i ] ) {
				$updated = true;
				unset( $chosen_teams[ $i ] );
			}
		}

		if ( $updated ) {
			// Reindex the array after `unset()`, otherwise `serialize()` will create blank items.
			$chosen_teams = array_values( $chosen_teams );

			$wpdb->update(
				$table,
				array( 'value' => maybe_serialize( $chosen_teams ) ),
				array( 'id'    => $row['id'] )
			);
		}
	}
}

/**
 * Remove invalid hours values, now that they're prevented from being entered.
 *
 * @see https://github.com/WordPress/five-for-the-future/issues/125
 */
function update_inaccurate_hours() {
	global $wpdb;

	$query = $wpdb->prepare( "
		UPDATE `bpmain_bp_xprofile_data`
		SET value = 0
		WHERE
			field_id = %d AND
			(
				value > 60 OR
				value REGEXP '[a-zA-Z]'
			)
		",
		XProfile\FIELD_IDS['hours_per_week']
	);

	$wpdb->query( $query );
}
