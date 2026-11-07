<?php

use function WordPressDotOrg\FiveForTheFuture\Pledge\{ bypass_jetpack_search, deactivate, deactivate_inactive_pledges };
use function WordPressDotOrg\FiveForTheFuture\Auth\get_authentication_url;
use const WordPressDotOrg\FiveForTheFuture\Auth\TOKEN_PREFIX;
use const WordPressDotOrg\FiveForTheFuture\Pledge\CPT_ID as PLEDGE_POST_TYPE;
use const WordPressDotOrg\FiveForTheFuture\Pledge\DEACTIVE_STATUS;
use const WordPressDotOrg\FiveForTheFuture\Contributor\CPT_ID as CONTRIBUTOR_POST_TYPE;

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

	/**
	 * Deactivating a pledge must revoke its outstanding confirmation link, so that a copy already sitting in a
	 * mailbox cannot be followed afterwards to undo the deactivation.
	 *
	 * @covers ::deactivate
	 */
	public function test_deactivate_revokes_the_confirmation_token() {
		$pledge_id = self::factory()->post->create( array(
			'post_type'   => PLEDGE_POST_TYPE,
			'post_status' => 'draft',
		) );
		$page_id   = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$token_key = TOKEN_PREFIX . 'confirm_pledge_email';

		get_authentication_url( $pledge_id, 'confirm_pledge_email', $page_id );
		$this->assertNotEmpty( get_post_meta( $pledge_id, $token_key, true ), 'The pledge should start with a token.' );

		deactivate( $pledge_id );

		$this->assertEmpty( get_post_meta( $pledge_id, $token_key, true ) );
	}

	/**
	 * Create a published pledge with one contributor, and optionally the inactivity deactivation date.
	 *
	 * @param int|null $deactivation_date   Timestamp, or null for a pledge that never got the inactivity notice.
	 * @param string   $contributor_status 'publish' for a confirmed contributor, 'pending' for an open invitation.
	 * @return int The pledge ID.
	 */
	private function create_pledge_with_contributor( ?int $deactivation_date, string $contributor_status ): int {
		$pledge_id = self::factory()->post->create( array(
			'post_type'   => PLEDGE_POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => 'Inactive Co',
		) );
		update_post_meta( $pledge_id, '5ftf_org-pledge-email', 'admin@inactive.example' );

		/*
		 * Leave the cached contributor count empty. It needs the profiles table, which this class does not
		 * create, and the deactivation must count the contributors itself anyway.
		 */
		$cache_hook = 'WordPressDotOrg\FiveForTheFuture\PledgeMeta\maybe_update_single_cached_pledge_data';
		remove_action( 'transition_post_status', $cache_hook );

		self::factory()->post->create( array(
			'post_type'   => CONTRIBUTOR_POST_TYPE,
			'post_status' => $contributor_status,
			'post_parent' => $pledge_id,
			'post_title'  => get_userdata( 1 )->user_login, // Test_Stats relies on fixed user IDs, so do not create users.
		) );

		add_action( 'transition_post_status', $cache_hook, 10, 3 );

		if ( null !== $deactivation_date ) {
			update_post_meta( $pledge_id, '5ftf_inactive_deactivate_date', $deactivation_date );
		}

		return $pledge_id;
	}

	/**
	 * Date offset from now, contributor status, expected pledge status, whether the date is kept, emails sent.
	 */
	public function data_inactive_pledges(): array {
		return array(
			'date passed, no confirmed contributors' => array( - DAY_IN_SECONDS, 'pending', DEACTIVE_STATUS, '', 1 ),
			'date passed, a confirmed contributor'   => array( - DAY_IN_SECONDS, 'publish', 'publish', '', 0 ),
			'date still ahead'                       => array( DAY_IN_SECONDS, 'pending', 'publish', 'kept', 0 ),
			'no inactivity notice'                   => array( null, 'pending', 'publish', '', 0 ),
		);
	}

	/**
	 * Once the date from the inactivity notice has passed, a pledge without confirmed contributors is
	 * deactivated and its admin is told, and one with confirmed contributors goes back into the reminders.
	 *
	 * @dataProvider data_inactive_pledges
	 * @covers ::deactivate_inactive_pledges
	 */
	public function test_deactivate_inactive_pledges( ?int $offset, string $contributor_status, string $expected_status, string $date, int $emails ) {
		$deactivation_date = null === $offset ? null : time() + $offset;
		$pledge_id         = $this->create_pledge_with_contributor( $deactivation_date, $contributor_status );
		reset_phpmailer_instance();

		deactivate_inactive_pledges();

		$this->assertSame( $expected_status, get_post_status( $pledge_id ) );
		$this->assertSame( 'kept' === $date ? (string) $deactivation_date : '', get_post_meta( $pledge_id, '5ftf_inactive_deactivate_date', true ) );

		$sent = tests_retrieve_phpmailer_instance()->mock_sent;
		$this->assertCount( $emails, $sent );
		if ( $emails ) {
			$this->assertSame( 'admin@inactive.example', $sent[0]['to'][0][0] );
			$this->assertSame( 'Pledge removed from Five for the Future', $sent[0]['subject'] );
		}
	}

	/**
	 * A pledge that was deactivated and then reactivated starts the review cycle again, rather than being
	 * deactivated by the next run because of an old date.
	 *
	 * @covers ::deactivate
	 * @covers ::deactivate_inactive_pledges
	 */
	public function test_reactivated_pledge_is_not_deactivated_again() {
		$pledge_id = $this->create_pledge_with_contributor( time() - DAY_IN_SECONDS, 'pending' );

		deactivate( $pledge_id, false, 'Site admin deactivated via wp-admin list table.' );
		wp_update_post( array(
			'ID'          => $pledge_id,
			'post_status' => 'publish',
		) );
		deactivate_inactive_pledges();

		$this->assertSame( 'publish', get_post_status( $pledge_id ) );
	}
}
