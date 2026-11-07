<?php

use WordPressDotOrg\FiveForTheFuture\{ Contributor, XProfile };
use WordPressDotOrg\FiveForTheFuture\Tests\Helpers as TestHelpers;

defined( 'WPINC' ) || die();

/**
 * @group xprofile
 */
class Test_XProfile extends WP_UnitTestCase {
	protected static $users;
	protected static $pledges;

	/**
	 * Run once when class loads.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$fixtures      = TestHelpers\database_setup_before_class( self::factory() );
		self::$users   = $fixtures['users'];
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
	 * Add users to a pledge as confirmed contributors.
	 *
	 * @param WP_Post   $pledge
	 * @param WP_User[] $users
	 */
	private function add_confirmed_contributors( WP_Post $pledge, array $users ): void {
		$contributor_ids = Contributor\add_pledge_contributors( $pledge->ID, wp_list_pluck( $users, 'user_login' ) );

		foreach ( $contributor_ids as $contributor_id ) {
			wp_update_post( array(
				'ID'          => $contributor_id,
				'post_status' => 'publish',
			) );
		}
	}

	/**
	 * Without a split on their profile, a contributor's total hours count toward the pledge, as before.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_aggregate_contributor_data_for_pledge
	 */
	public function test_pledge_hours_without_split_count_the_total(): void {
		$tenup = self::$pledges['10up'];
		$this->add_confirmed_contributors( $tenup, array( self::$users['jane'], self::$users['ashish'] ) );

		$data = XProfile\get_aggregate_contributor_data_for_pledge( $tenup->ID );

		$this->assertSame( 75, $data['hours'] );
	}

	/**
	 * With a split, only the hours a contributor gives through this pledge count toward it, and their own
	 * time counts toward no pledge.
	 *
	 * The split is saved under the meta key that profiles.wordpress.org uses, written out instead of
	 * `HOURS_SPLIT_META_KEY`, so that a wrong key fails this test.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_aggregate_contributor_data_for_pledge
	 */
	public function test_pledge_hours_count_the_split(): void {
		$tenup    = self::$pledges['10up'];
		$bluehost = self::$pledges['bluehost'];
		$jane     = self::$users['jane'];
		$this->add_confirmed_contributors( $tenup, array( $jane, self::$users['ashish'] ) );
		$this->add_confirmed_contributors( $bluehost, array( $jane ) );

		// Jane's profile total is 40: 10 for 10up, 25 for BlueHost and 5 of her own time.
		update_user_meta( $jane->ID, 'wporg_5ftf_hours_breakdown', array(
			$tenup->ID    => 10,
			$bluehost->ID => 25,
			'self'        => 5,
		) );

		$this->assertSame( 45, XProfile\get_aggregate_contributor_data_for_pledge( $tenup->ID )['hours'] );
		$this->assertSame( 25, XProfile\get_aggregate_contributor_data_for_pledge( $bluehost->ID )['hours'] );
	}

	/**
	 * A split that gives no hours to a pledge counts nothing toward it.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_aggregate_contributor_data_for_pledge
	 */
	public function test_pledge_hours_are_zero_when_the_split_skips_the_pledge(): void {
		$tenup    = self::$pledges['10up'];
		$bluehost = self::$pledges['bluehost'];
		$jane     = self::$users['jane'];
		$this->add_confirmed_contributors( $tenup, array( $jane ) );

		update_user_meta( $jane->ID, XProfile\HOURS_SPLIT_META_KEY, array(
			$bluehost->ID => 30,
			'self'        => 10,
		) );

		$this->assertSame( 0, XProfile\get_aggregate_contributor_data_for_pledge( $tenup->ID )['hours'] );
	}

	/**
	 * A split with no hours in it says nothing, so the total counts toward the pledge.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_aggregate_contributor_data_for_pledge
	 */
	public function test_pledge_hours_ignore_an_empty_split(): void {
		$tenup = self::$pledges['10up'];
		$jane  = self::$users['jane'];
		$this->add_confirmed_contributors( $tenup, array( $jane ) );

		update_user_meta( $jane->ID, XProfile\HOURS_SPLIT_META_KEY, array(
			$tenup->ID => 0,
			'self'     => 0,
		) );

		$this->assertSame( 40, XProfile\get_aggregate_contributor_data_for_pledge( $tenup->ID )['hours'] );
	}

	/**
	 * A pledge never gets more hours from a contributor than their profile total.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_aggregate_contributor_data_for_pledge
	 */
	public function test_pledge_hours_from_a_split_stay_within_the_total(): void {
		$tenup = self::$pledges['10up'];
		$jane  = self::$users['jane'];
		$this->add_confirmed_contributors( $tenup, array( $jane ) );

		update_user_meta( $jane->ID, XProfile\HOURS_SPLIT_META_KEY, array( $tenup->ID => 50 ) );

		$this->assertSame( 40, XProfile\get_aggregate_contributor_data_for_pledge( $tenup->ID )['hours'] );
	}

	/**
	 * The Core Program Team gets a badge instead of the unknown "?" one.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\XProfile\get_association_classes
	 */
	public function test_core_program_team_has_a_badge(): void {
		$this->assertNotContains( 'badge-unknown', XProfile\get_association_classes( 'Core Program Team' ) );
	}
}
