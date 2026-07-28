<?php

use function WordPressDotOrg\FiveForTheFuture\Pledge\{ bypass_jetpack_search };

defined( 'WPINC' ) || die();

/**
 * @group pledge
 */
class Test_Pledge extends WP_UnitTestCase {
	/**
	 * Jetpack Search must not handle the pledge search.
	 *
	 * It drops every `exclude_from_search` post type before querying Elasticsearch, which
	 * leaves it with nothing to search and makes it call `WP_Query::set_404()`. That turned
	 * every pledge search into a 404, even though the database query found the pledges.
	 *
	 * @covers ::bypass_jetpack_search
	 */
	public function test_bypass_jetpack_search_declines_main_search_query() {
		$this->go_to( '/?s=example' );

		global $wp_query;

		$this->assertTrue( $wp_query->is_main_query(), 'Expected the global query to be the main query.' );
		$this->assertTrue( $wp_query->is_search(), 'Expected a search query.' );
		$this->assertFalse( bypass_jetpack_search( true, $wp_query ) );
	}

	/**
	 * Queries other than the main search should be left alone, so that Jetpack Search keeps
	 * whatever behaviour it would otherwise have.
	 *
	 * @covers ::bypass_jetpack_search
	 */
	public function test_bypass_jetpack_search_ignores_other_queries() {
		$this->go_to( '/?s=example' );

		$secondary = new WP_Query( array( 's' => 'example' ) );
		$this->assertFalse( $secondary->is_main_query(), 'Expected a secondary query.' );
		$this->assertTrue( bypass_jetpack_search( true, $secondary ), 'Secondary search queries should pass through.' );

		$this->go_to( '/' );

		global $wp_query;
		$this->assertFalse( $wp_query->is_search(), 'Expected a non-search query.' );
		$this->assertTrue( bypass_jetpack_search( true, $wp_query ), 'Non-search queries should pass through.' );
	}
}
