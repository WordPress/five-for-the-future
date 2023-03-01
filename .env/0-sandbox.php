<?php
/*
 * These are stubs for closed source code, or things that only apply to local environments.
 */


namespace {
	defined( 'WPINC' ) || die();

	define( 'WPORG_SUPPORT_FORUMS_BLOGID', 419 );

	/**
	 * Stub.
	 */
	function bump_stats_extra( $name, $value, $views = 1 ) {
	}
}

namespace WordPressdotorg\MU_Plugins\Helpers {
	/**
	 * Stub.
	 */
	function natural_language_join( array $list, $conjunction = 'and' ): string {
		return implode( ', ', $list );
	}
}
