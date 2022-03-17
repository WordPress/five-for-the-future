<?php

/*
 * Deactivate pledges
 *
 * Usage: wp eval-file deactivate-pledges.php --url=https://wordpress.org/five-for-the-future/
 */

namespace WordPressDotOrg\FiveForTheFuture\Bin;

use function WordPressDotOrg\FiveForTheFuture\Pledge\{ deactivate };
use const WordPressDotOrg\FiveForTheFuture\Pledge\{ DEACTIVE_STATUS };
use WP_Post;
use WP_ClI;

defined( 'WP_CLI' ) || die( 'Nope' );

wp_debug_mode();    // re-set `display_errors` after WP-CLI overrides it, see https://github.com/wp-cli/wp-cli/issues/706#issuecomment-203610437

/** @var string $file The filename of the current script */
/** @var array  $args The arguments passed to this script from the command line */
main( $file, $args );


/**
 * The main controller
 */
function main( $file, $args ) {
	WP_CLI::line();

	// The 5ftf plugin has to be loaded.
	if ( 'local' !== wp_get_environment_type() && 668 !== get_current_blog_id() ) {
		WP_ClI::error( 'This must be ran on the 5ftF site, please use the `--url=https://wordpress.org/five-for-the-future/` argument.' );
	}

	$emails     = array();
	$reason     = '';
	$pledge_ids = array(
	);

	WP_ClI::warning( sprintf(
		"The following pledge IDs will be deactivated: \n\t %s \n\nThe reason is: %s \n",
		implode( ', ', $pledge_ids ),
		$reason
	) );
	WP_ClI::confirm( 'Are you sure you want to proceed?' );

	foreach ( $pledge_ids as $id ) {
		$pledge = get_post( $id );

		if ( ! $pledge instanceof WP_Post || '5ftf_pledge' !== $pledge->post_type ) {
			WP_ClI::error( "$id is not a pledge post.", false );
			continue;
		}

		// We don't want to email someone twice if they were already deactivated.
		if ( DEACTIVE_STATUS === $pledge->post_status ) {
			WP_ClI::warning( sprintf( "%s (%d) is already deactivated.", $pledge->post_title, $id ) );
			continue;
		}

		$emails[] = get_post_meta( $id, '5ftf_org-pledge-email', true );
		deactivate( $id, false, $reason );
	}

	WP_CLI::line( "\n" );
	WP_CLI::success( 'Done' );

	WP_ClI::line( "Email addresses of deactivated organizations: \n" );
	WP_ClI::line( implode( ', ', $emails ) );
}
