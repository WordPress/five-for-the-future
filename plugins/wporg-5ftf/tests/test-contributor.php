<?php

use WordPressDotOrg\FiveForTheFuture\{ Contributor, Pledge, XProfile };
use WordPressDotOrg\FiveForTheFuture\Tests\Helpers as TestHelpers;

defined( 'WPINC' ) || die();

/**
 * Some of these are integration tests rather than unit tests. They target the the highest functions in the call stack
 * in order to test everything beneath them, to the extent that that's practical. `INPUT_POST` can't be mocked,
 * so functions that reference it can't be used.
 *
 * Mocking that can become unwieldy, though, so sometimes unit tests are more practical.
 *
 * @group contributor
 */
class Test_Contributor extends WP_UnitTestCase {
	protected static $users;
	protected static $pages;
	protected static $pledges;

	/**
	 * Run once when class loads.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$fixtures      = TestHelpers\database_setup_before_class( self::factory() );
		self::$users   = $fixtures['users'];
		self::$pages   = $fixtures['pages'];
		self::$pledges = $fixtures['pledges'];
	}

	/**
	 * Run before every test.
	 */
	public function set_up() {
		parent::set_up();
		TestHelpers\database_set_up( array_values( wp_list_pluck( self::$users, 'ID' ) ) );
		reset_phpmailer_instance();
	}

	/**
	 * Run once after all tests are finished.
	 */
	public static function tear_down_after_class() {
		parent::tear_down_after_class();
		TestHelpers\database_tear_down_after_class();
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\remove_pledge_contributors
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\remove_contributor
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\add_pledge_contributors
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_contributor_user_data
	 * @covers WordPressDotOrg\FiveForTheFuture\Pledge\deactivate
	 */
	public function test_data_reset_once_no_active_sponsors() : void {
		// Setup scenario where Jane is sponsored by two companies.
		$mailer                = tests_retrieve_phpmailer_instance();
		$jane                  = self::$users['jane'];
		$jane_contribution     = XProfile\get_contributor_user_data( $jane->ID );
		$tenup                 = self::$pledges['10up'];
		$bluehost              = self::$pledges['bluehost'];
		$tenup_contributors    = Contributor\add_pledge_contributors( $tenup->ID, array( $jane->user_login ) );
		$bluehost_contributors = Contributor\add_pledge_contributors( $bluehost->ID, array( $jane->user_login ) );
		$tenup_jane_id         = $tenup_contributors[ $jane->user_login ];
		$bluehost_jane_id      = $bluehost_contributors[ $jane->user_login ];

		wp_update_post( array(
			'ID'          => $tenup_jane_id,
			'post_status' => 'publish',
		) );
		wp_update_post( array(
			'ID'          => $bluehost_jane_id,
			'post_status' => 'publish',
		) );

		$bluehost_jane = get_post( $bluehost_jane_id );

		$this->assertSame( 'publish', $bluehost->post_status );
		$this->assertSame( 'publish', $bluehost_jane->post_status );
		$this->assertSame( 40, $jane_contribution['hours_per_week'] );
		$this->assertContains( 'Core Team', $jane_contribution['team_names'] );

		// Deactivating a pledge shouldn't trigger a data resets if they have another active sponsor.
		Pledge\deactivate( $bluehost->ID, false );

		$bluehost          = get_post( $bluehost->ID );
		$bluehost_jane     = get_post( $bluehost_jane->ID );
		$tenup_jane        = get_post( $tenup_jane_id );
		$jane_contribution = XProfile\get_contributor_user_data( $jane->ID );

		$this->assertSame( Pledge\DEACTIVE_STATUS, $bluehost->post_status );
		$this->assertSame( 'trash', $bluehost_jane->post_status );
		$this->assertSame( 'publish', $tenup_jane->post_status );
		$this->assertSame( 40, $jane_contribution['hours_per_week'] );
		$this->assertContains( 'Core Team', $jane_contribution['team_names'] );

		$this->assertContains( $jane->user_email, $mailer->mock_sent[0]['to'][0] );
		$this->assertSame( "Removed from $bluehost->post_title Five for the Future pledge", $mailer->mock_sent[0]['subject'] );

		// Once the last sponsor has been deactivated, contribution data should be reset.
		Pledge\deactivate( $tenup->ID, false );

		$tenup             = get_post( $tenup->ID );
		$tenup_jane        = get_post( $tenup_jane_id );
		$jane_contribution = XProfile\get_contributor_user_data( $jane->ID );

		$this->assertSame( Pledge\DEACTIVE_STATUS, $tenup->post_status );
		$this->assertSame( 'trash', $tenup_jane->post_status );
		$this->assertSame( 0, $jane_contribution['hours_per_week'] );
		$this->assertEmpty( $jane_contribution['team_names'] );
		$this->assertContains( $jane->user_email, $mailer->mock_sent[1]['to'][0] );
		$this->assertSame( "Removed from $tenup->post_title Five for the Future pledge", $mailer->mock_sent[1]['subject'] );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\remove_pledge_contributors
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\remove_contributor
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\add_pledge_contributors
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_contributor_user_data
	 * @covers WordPressDotOrg\FiveForTheFuture\Pledge\deactivate
	 */
	public function test_data_not_reset_when_unconfirmed_sponsor() : void {
		// Setup scenario where Jane was invited to join a company but didn't respond.
		$mailer             = tests_retrieve_phpmailer_instance();
		$jane               = self::$users['jane'];
		$jane_contribution  = XProfile\get_contributor_user_data( $jane->ID );
		$tenup              = self::$pledges['10up'];
		$tenup_contributors = Contributor\add_pledge_contributors( $tenup->ID, array( $jane->user_login ) );
		$tenup_jane_id      = $tenup_contributors[ $jane->user_login ];

		wp_update_post( array(
			'ID'          => $tenup_jane_id,
			'post_status' => 'pending',
		) );

		$tenup_jane = get_post( $tenup_jane_id );

		$this->assertSame( 'publish', $tenup->post_status );
		$this->assertSame( 'pending', $tenup_jane->post_status );
		$this->assertSame( 40, $jane_contribution['hours_per_week'] );
		$this->assertContains( 'Core Team', $jane_contribution['team_names'] );

		// Deactivating a pledge shouldn't trigger a data resets if they haven't confirmed their connection to the company.
		Pledge\deactivate( $tenup->ID, false );

		$tenup             = get_post( $tenup->ID );
		$tenup_jane        = get_post( $tenup_jane_id );
		$jane_contribution = XProfile\get_contributor_user_data( $jane->ID );

		$this->assertSame( Pledge\DEACTIVE_STATUS, $tenup->post_status );
		$this->assertSame( 'trash', $tenup_jane->post_status );
		$this->assertSame( 40, $jane_contribution['hours_per_week'] );
		$this->assertContains( 'Core Team', $jane_contribution['team_names'] );

		$this->assertEmpty( $mailer->mock_sent );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\remove_contributor
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\add_pledge_contributors
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_contributor_user_data
	 */
	public function test_data_reset_when_single_contributor_removed_from_pledge() : void {
		// Setup scenario where Jane and Ashish are sponsored by a company.
		$mailer              = tests_retrieve_phpmailer_instance();
		$jane                = self::$users['jane'];
		$jane_contribution   = XProfile\get_contributor_user_data( $jane->ID );
		$ashish              = self::$users['ashish'];
		$ashish_contribution = XProfile\get_contributor_user_data( $ashish->ID );
		$tenup               = self::$pledges['10up'];
		$tenup_contributors  = Contributor\add_pledge_contributors( $tenup->ID, array( $jane->user_login, $ashish->user_login ) );
		$tenup_jane_id       = $tenup_contributors[ $jane->user_login ];
		$tenup_ashish_id     = $tenup_contributors[ $ashish->user_login ];

		wp_update_post( array(
			'ID'          => $tenup_jane_id,
			'post_status' => 'publish',
		) );
		wp_update_post( array(
			'ID'          => $tenup_ashish_id,
			'post_status' => 'publish',
		) );

		$tenup_jane   = get_post( $tenup_jane_id );
		$tenup_ashish = get_post( $tenup_ashish_id );

		$this->assertSame( 'publish', $tenup_ashish->post_status );
		$this->assertSame( 'publish', $tenup_jane->post_status );
		$this->assertSame( 40, $jane_contribution['hours_per_week'] );
		$this->assertContains( 'Core Team', $jane_contribution['team_names'] );
		$this->assertSame( 35, $ashish_contribution['hours_per_week'] );
		$this->assertContains( 'Documentation Team', $ashish_contribution['team_names'] );

		// Removing Jane should reset her data, but leave Ashish unaffected.
		Contributor\remove_contributor( $tenup_jane_id );

		$tenup_jane          = get_post( $tenup_jane_id );
		$jane_contribution   = XProfile\get_contributor_user_data( $jane->ID );
		$tenup_ashish        = get_post( $tenup_ashish_id );
		$ashish_contribution = XProfile\get_contributor_user_data( $ashish->ID );

		$this->assertSame( 'trash', $tenup_jane->post_status );
		$this->assertSame( 'publish', $tenup_ashish->post_status );
		$this->assertSame( 0, $jane_contribution['hours_per_week'] );
		$this->assertEmpty( $jane_contribution['team_names'] );
		$this->assertSame( 35, $ashish_contribution['hours_per_week'] );
		$this->assertContains( 'Documentation Team', $ashish_contribution['team_names'] );
		$this->assertCount( 1, $mailer->mock_sent );
		$this->assertContains( $jane->user_email, $mailer->mock_sent[0]['to'][0] );
		$this->assertSame( "Removed from $tenup->post_title Five for the Future pledge", $mailer->mock_sent[0]['subject'] );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Contributor\prune_unnotifiable_users
	 */
	public function test_prune_unnotifiable_users() {
		$contributors = array(
			'active + due for email' => array(
				'last_logged_in'             => strtotime( '1 week ago' ),
				'user_registered'            => strtotime( '1 year ago' ),
				'5ftf_last_inactivity_email' => 0,
			),

			'active + not due for email' => array(
				'last_logged_in'             => strtotime( '1 week ago' ),
				'user_registered'            => strtotime( '1 year ago' ),
				'5ftf_last_inactivity_email' => strtotime( '1 month ago' ),
			),

			'inactive + due for email' => array(
				'last_logged_in'             => strtotime( '4 months ago' ),
				'user_registered'            => strtotime( '1 year ago' ),
				'5ftf_last_inactivity_email' => strtotime( '4 months ago' ),
			),

			'inactive + not due for email' => array(
				'last_logged_in'             => strtotime( '4 months ago' ),
				'user_registered'            => strtotime( '1 year ago' ),
				'5ftf_last_inactivity_email' => strtotime( '2 months ago' ),
			),

			'new user' => array(
				'last_logged_in'             => 0,
				'user_registered'            => strtotime( '1 week ago' ),
				'5ftf_last_inactivity_email' => 0,
			),
		);

		$expected = array( 'inactive + due for email' );

		$actual = Contributor\prune_unnotifiable_users( $contributors );

		$this->assertSame( $expected, array_keys( $actual ) );
	}
}
