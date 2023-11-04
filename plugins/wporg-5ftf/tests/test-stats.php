<?php

use function WordPressDotOrg\FiveForTheFuture\Stats\{ get_snapshot_data };
use WordPressDotOrg\FiveForTheFuture\{ Contributor };
use WordPressDotOrg\FiveForTheFuture\Tests\Helpers as TestHelpers;

defined( 'WPINC' ) || die();

/**
 * @group stats
 */
class Test_Stats extends WP_UnitTestCase {
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
	}

	/**
	 * Run once after all tests are finished.
	 */
	public static function tear_down_after_class() {
		parent::tear_down_after_class();
		TestHelpers\database_tear_down_after_class();
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Stats\get_snapshot_data
	 */
	public function test_get_snapshot() : void {
		// Setup 2 company-sponsored contributors.
		$jane                = self::$users['jane'];
		$ashish              = self::$users['ashish'];
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

		$expected = array(
			'company_sponsored_hours' => 75,
			'self_sponsored_hours'    => 16,

			'team_company_sponsored_contributors' => array(
				'Core Team'          => 1,
				'Documentation Team' => 1,
			),

			'team_self_sponsored_contributors' => array(
				'Meta Team'      => 2,
				'Polyglots Team' => 1,
				'Training Team'  => 1,
			),

			'inactive_contributor_ids'               => array(
				0 => 9,
				1 => 11,
				2 => 13,
			),

			'companies'                              => 2,
			'company_sponsored_contributors'         => 2,
			'self_sponsored_contributors'            => 3,
			'self_sponsored_contributor_activity'    => 33.33,
			'company_sponsored_contributor_activity' => 50.0,
		);

		$actual = get_snapshot_data();

		$this->assertSame( $expected, $actual );
	}
}
