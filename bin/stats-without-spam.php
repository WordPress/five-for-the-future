<?php

/*
 * Trying to get a more accurate count than the `5ftf_stats` shortcode. This is just a stopgap until that project
 * is finished and the stats are wiped to start fresh.
 * @link https://github.com/WordPress/five-for-the-future/issues/120
 *
 * ⚠️ This may not be accurate in the future due to changes from automated cleanup efforts, and organic changes over time.
 * Make sure you verify the method below is still accurate before running again.
 * @link https://make.wordpress.org/project/2022/03/16/proposal-to-remove-spam-dormant-five-for-the-future-pledges/
 *
 * Usage: wp eval-file stats-without-spam.php --url=https://wordpress.org/five-for-the-future/
 */

namespace WordPressDotOrg\FiveForTheFuture\Bin;

use WordPressDotOrg\FiveForTheFuture\{Pledge, PledgeLog, PledgeMeta};
use WP_ClI, WP_Error;

const DATE_CLEANED_PLEDGES      = '2022-03-18';
const DATE_CLEANED_CONTRIBUTORS = '2022-05-25';

defined( 'WP_CLI' ) || die( 'Nope' );

wp_debug_mode();    // re-set `display_errors` after WP-CLI overrides it, see https://github.com/wp-cli/wp-cli/issues/706#issuecomment-203610437

main();


/**
 * The main controller
 */
function main() {
	WP_CLI::line();

	// The 5ftf plugin has to be loaded.
	if ( 'local' !== wp_get_environment_type() && 668 !== get_current_blog_id() ) {
		WP_ClI::error( 'This must be ran on the 5ftF site, please use the `--url=https://wordpress.org/five-for-the-future/` argument.' );
	}

	$valid_pledges = get_valid_pledges();
	$month_counts  = get_month_counts( $valid_pledges );

	// launched at SotW nov 2019. Include soft launch and those who joined shortly after.
	$number_after_launch = $month_counts['2019-10'] + $month_counts['2019-11'] + $month_counts['2019-12'];

	WP_CLI::line( '# added each month: ' );
	print_r( $month_counts ); // todo this isn't counting how many were subtracted

	WP_CLI::line( "# soon after launch: " . $number_after_launch );
	WP_CLI::line( "# now: " . count( filter_active_pledges( $valid_pledges ) ) );

	WP_CLI::line( "\n" );
	WP_CLI::success( 'Done' );
}

function get_valid_pledges(): array {
	$valid_pledges = array();
	$all_companies = get_posts( array(
		'post_type'      => Pledge\CPT_ID,
		'post_status'    => 'any',
		'posts_per_page' => -1,
	) );

	foreach ( $all_companies as $company ) {
		$log            = PledgeLog\get_pledge_log( $company->ID );
		$creation_entry = get_creation_entry( $log );
		$creation_date  = $creation_entry[0]['timestamp'] ?? null;

		if ( 1 !== count( $creation_entry ) || empty( $creation_entry[0]['timestamp'] ) ) {
			WP_CLI::error( new WP_Error(
				'log_mismatch',
				"log doesn't match expectations",
				array( $company, $creation_entry )
			) );
		}

		// These companies haven't been manually verified, and many look just as inaccurate as the ones that were cleaned.
		// ⚠️ This is only meaningful because it just happened a few months before this script was written. It will become
		// less meaningful as time passes.
		if ( $creation_date >= strtotime( DATE_CLEANED_PLEDGES ) ) {
			continue;
		}

		// These were manually determined to be spam/dormant.
		if ( Pledge\DEACTIVE_STATUS === $company->post_status ) {
			$messages = implode( ' ', wp_list_pluck( $log, 'message' ) );

			if ( str_contains( $messages, 'Manually removing spam/dormant pledges' ) ) {
				continue;
			}
		}

		// Don't remove companies that don't currently have any confirmed contributors, because they may have had them in the past.

		$valid_pledges[] = $company;
	}

	return $valid_pledges;
}

function get_creation_entry( array $log ): array {
	$creation_entry = array_filter( $log, function( $entry ) {
		return 'pledge_created' === $entry['type'];
	} );

	return array_values( $creation_entry ); // remove index gaps
}

function get_month_counts( array $valid_pledges ) : array {
	$month_counts = array();

	foreach ( $valid_pledges as $pledge ) {
		$log            = PledgeLog\get_pledge_log( $pledge->ID );
		$creation_entry = get_creation_entry( $log );
		$month_key      = date( 'Y-m', $creation_entry[0]['timestamp'] );

		$month_counts[ $month_key ]++;
	}

	return $month_counts;
}

function filter_active_pledges( array $pledges ): array {
	return array_filter( $pledges, function( $pledge ) {
		$active_contributors = get_post_meta( $pledge->ID, PledgeMeta\META_PREFIX . 'pledge-confirmed-contributors', true );

		return 'publish' === $pledge->post_status && count( $active_contributors ) > 0;
	} );
}
