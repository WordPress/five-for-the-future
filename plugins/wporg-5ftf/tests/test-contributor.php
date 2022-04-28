<?php

use WordPressDotOrg\FiveForTheFuture\{ Contributor, Pledge, XProfile };

defined( 'WPINC' ) || die();

/**
 * These are integration tests rather than unit tests. They target the the highest functions in the call stack
 * in order to test everything beneath them, to the extent that that's practical. `INPUT_POST` can't be mocked,
 * so functions that reference it can't be used.
 *
 * @group contributor
 */
class Test_Contributor extends WP_UnitTestCase {
	protected static $user_jane_id;
	protected static $user_ashish_id;
	protected static $page_for_organizations;
	protected static $pledge_bluehost_id;
	protected static $pledge_10up_id;

	/**
	 * Run once when class loads.
	 */
	public static function set_up_before_class() {
		global $wpdb;

		parent::set_up_before_class();

		$wpdb->query( "
			CREATE TABLE `bpmain_bp_xprofile_data` (
				`id` bigint unsigned NOT NULL AUTO_INCREMENT,
				`field_id` bigint unsigned NOT NULL DEFAULT '0',
				`user_id` bigint unsigned NOT NULL DEFAULT '0',
				`value` longtext NOT NULL,
				`last_updated` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
				PRIMARY KEY (`id`),
				KEY `field_id` (`field_id`),
				KEY `user_id` (`user_id`)
			)
			ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3
		" );

		self::$user_jane_id = self::factory()->user->create( array(
			'user_login' => 'jane',
			'user_email' => 'jane@example.org',
		) );

		self::$user_ashish_id = self::factory()->user->create( array(
			'user_login' => 'ashish',
			'user_email' => 'ashish@example.org',
		) );

		self::$page_for_organizations = get_post( self::factory()->post->create( array(
			'post_type'   => 'page',
			'post_title'  => 'For Organizations',
			'post_status' => 'publish',
		) ) );

		$GLOBALS['post'] = self::$page_for_organizations; // `create_new_pledge()` assumes this exists.

		self::$pledge_10up_id     = Pledge\create_new_pledge( '10up' );
		self::$pledge_bluehost_id = Pledge\create_new_pledge( 'BlueHost' );

		wp_update_post( array(
			'ID'          => self::$pledge_bluehost_id,
			'post_status' => 'publish',
		) );
		wp_update_post( array(
			'ID'          => self::$pledge_10up_id,
			'post_status' => 'publish',
		) );
	}

	/**
	 * Run before every test.
	 */
	public function set_up() {
		global $wpdb;

		parent::set_up();

		$wpdb->query( 'TRUNCATE TABLE `bpmain_bp_xprofile_data` ' );

		$wpdb->query( $wpdb->prepare( "
			INSERT INTO `bpmain_bp_xprofile_data`
			(`id`, `field_id`, `user_id`, `value`, `last_updated`)
			VALUES
				(NULL, 29, %d, '40', '2019-12-02 10:00:00' ),
				(NULL, 30, %d, 'a:1:{i:0;s:9:\"Core Team\";}', '2019-12-03 11:00:00' ),
				(NULL, 29, %d, '35', '2019-12-02 10:00:00' ),
				(NULL, 30, %d, 'a:1:{i:0;s:18:\"Documentation Team\";}', '2019-12-03 11:00:00' )",
			self::$user_jane_id,
			self::$user_jane_id,
			self::$user_ashish_id,
			self::$user_ashish_id
		) );

		reset_phpmailer_instance();
	}

	/**
	 * Run once after all tests are finished.
	 */
	public static function tear_down_after_class() {
		global $wpdb;

		parent::tear_down_after_class();

		$wpdb->query( 'DROP TABLE `bpmain_bp_xprofile_data` ' );
	}

	/**
	 * @covers ::remove_pledge_contributors
	 * @covers ::remove_contributors
	 */
	public function test_data_reset_once_no_active_sponsors() : void {
		// Setup scenario where Jane is sponsored by two companies.
		$mailer                = tests_retrieve_phpmailer_instance();
		$jane                  = get_user_by( 'id', self::$user_jane_id );
		$jane_contribution     = XProfile\get_contributor_user_data( $jane->ID );
		$tenup                 = get_post( self::$pledge_10up_id );
		$bluehost              = get_post( self::$pledge_bluehost_id );
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
	 * @covers ::remove_pledge_contributors
	 * @covers ::remove_contributors
	 */
	public function test_data_not_reset_when_unconfirmed_sponsor() : void {
		// Setup scenario where Jane was invited to join a company but didn't respond.
		$mailer             = tests_retrieve_phpmailer_instance();
		$jane               = get_user_by( 'id', self::$user_jane_id );
		$jane_contribution  = XProfile\get_contributor_user_data( $jane->ID );
		$tenup              = get_post( self::$pledge_10up_id );
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
	 * @covers ::remove_contributors
	 */
	public function test_data_reset_when_single_contributor_removed_from_pledge() : void {
		// Setup scenario where Jane and Ashish are sponsored by a company.
		$mailer              = tests_retrieve_phpmailer_instance();
		$jane                = get_user_by( 'id', self::$user_jane_id );
		$jane_contribution   = XProfile\get_contributor_user_data( $jane->ID );
		$ashish              = get_user_by( 'id', self::$user_ashish_id );
		$ashish_contribution = XProfile\get_contributor_user_data( $ashish->ID );
		$tenup               = get_post( self::$pledge_10up_id );
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
}
