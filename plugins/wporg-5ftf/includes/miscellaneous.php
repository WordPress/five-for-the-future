<?php

namespace WordPressDotOrg\FiveForTheFuture\Miscellaneous;

add_action( 'after_setup_theme', function() {
	// These alternate versions don't exist for this subsite, so the links would just lead to 404 errors.
	remove_action( 'wp_head', 'WordPressdotorg\Theme\hreflang_link_attributes' );
} );
