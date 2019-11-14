<?php

use function WordPressDotOrg\FiveForTheFuture\Email\{ get_authentication_url, is_valid_authentication_token };
use const WordPressDotOrg\FiveForTheFuture\Email\{ TOKEN_PREFIX };
use const WordPressDotOrg\FiveForTheFuture\Pledge\CPT_ID as PLEDGE_POST_TYPE;

defined( 'WPINC' ) || die();

class Test_Email extends WP_UnitTestCase {
	// phpcs:ignore PSR2.Classes.PropertyDeclaration.Multiple
	protected static $valid_pledge, $valid_action, $valid_action_page, $valid_action_url, $valid_token;

	/**
	 * Setup fixtures that are shared across all tests.
	 */
	public static function wpSetUpBeforeClass() {
		$valid_pledge_params = array(
			'post_type'   => PLEDGE_POST_TYPE,
			'post_title'  => 'Valid Pledge',
			'post_status' => 'publish',
		);

		$valid_action_page_params = array(
			'post_type'   => 'page',
			'post_title'  => 'For Organizers',
			'post_status' => 'publish',
		);

		$valid_pledge_id = self::factory()->post->create( $valid_pledge_params );

		self::$valid_pledge = get_post( $valid_pledge_id );

		$valid_action_page_id    = self::factory()->post->create( $valid_action_page_params );
		self::$valid_action_page = get_post( $valid_action_page_id );

		self::$valid_action = 'confirm_pledge_email';
		// todo better example action to use, like contributor verifying participation? or is this one just as good?
			// should probably use the manage one once that's implemented, b/c should test the `view` context and the `update` context.
			// use this one for now, though.

		self::verify_before_class_fixtures();
	}

	/**
	 * Verify whether or not the fixtures were setup correctly.
	 *
	 * @return void
	 */
	protected static function verify_before_class_fixtures() {
		self::assertSame( 'object',         gettype( self::$valid_action_page ) );
		self::assertSame( 'For Organizers', self::$valid_action_page->post_title );
		self::assertSame( 'object',         gettype( self::$valid_pledge ) );
		self::assertSame( 'Valid Pledge',   self::$valid_pledge->post_title );
		self::assertSame( PLEDGE_POST_TYPE, self::$valid_pledge->post_type );
	}

	/**
	 * Setup fixtures that are unique for each test.
	 */
	public function setUp() {
		parent::setUp();

		/*
		 * `get_authentication_url()` should create a valid token in the database.
		 *
		 * This must be called before every test, because the process of verifying a valid token will delete it.
		 */
		self::$valid_action_url = get_authentication_url( self::$valid_pledge->ID, self::$valid_action, self::$valid_action_page->ID );
		self::$valid_token      = get_post_meta( self::$valid_pledge->ID, TOKEN_PREFIX . self::$valid_action, true );

		// Verify that the fixtures are setup correctly.
		$action_url_args = wp_parse_args( wp_parse_url( self::$valid_action_url, PHP_URL_QUERY ) );

		$this->assertSame( 'array', gettype( self::$valid_token ) );
		$this->assertSame( $action_url_args['action'], self::$valid_action );
		$this->assertSame( $action_url_args['auth_token'], self::$valid_token['value'] );
	}

	/**
	 * @covers ::is_valid_authentication_token
	 */
	public function test_valid_token_accepted() {
		$verified = is_valid_authentication_token( self::$valid_pledge->ID, self::$valid_action, self::$valid_token['value'] );

		$this->assertTrue( $verified );

		// todo test that `view` and `update` contexts work as well, when those are added
			// maybe need to test some failures for that too.
	}

	/**
	 * @covers ::is_valid_authentication_token
	 *
	 * @dataProvider data_invalid_token_rejected
	 */
	public function test_invalid_token_rejected( $invalid_token ) {
		/*
		 * It's expected that some of the values passed in won't have a `value` item, so fallback to the item
		 * itself in those cases, to avoid PHPUnit throwing an exception.
		 */
		$invalid_token_value = $invalid_token['value'] ?? $invalid_token;
		$verified            = is_valid_authentication_token( self::$valid_pledge->ID, self::$valid_action, $invalid_token_value );

		$this->assertSame( false, $verified );
	}

	/**
	 * Test that invalid tokens are rejected.
	 *
	 * Note that data providers can't access fixtures.
	 * See https://phpunit.readthedocs.io/en/7.4/writing-tests-for-phpunit.html#data-providers.
	 *
	 * @covers ::is_valid_authentication_token
	 */
	public function data_invalid_token_rejected() {
		return array(
			'non-existent-token' => array( false ), // Simulates `get_post_meta()` return value.
			'wrong-data-type'    => array( 'this string is not an array' ),
			'wrong-array-items'  => array( 'this' => "doesn't have `value` and `expiration` items" ),

			'invalid-value'      => array(
				array(
					'value'      => 'Valid tokens will never contain special characters like !@#$%^&*()',
					'expiration' => time() + HOUR_IN_SECONDS,
				),
			),
		);
	}

	/**
	 * @covers ::is_valid_authentication_token
	 */
	public function test_expired_token_rejected() {
		$expired_token               = self::$valid_token;
		$expired_token['expiration'] = time() - 1;

		update_post_meta( self::$valid_pledge->ID, TOKEN_PREFIX . self::$valid_action, $expired_token );

		$verified = is_valid_authentication_token( self::$valid_pledge->ID, self::$valid_action, self::$valid_token['value'] );

		$this->assertSame( false, $verified );
	}

	/**
	 * @covers ::is_valid_authentication_token
	 */
	public function test_used_token_rejected() {
		// The token should be deleted once it's used/verified for the first time.
		$first_verification  = is_valid_authentication_token( self::$valid_pledge->ID, self::$valid_action, self::$valid_token['value'] );
		$second_verification = is_valid_authentication_token( self::$valid_pledge->ID, self::$valid_action, self::$valid_token['value'] );

		$this->assertSame( true, $first_verification );
		$this->assertSame( false, $second_verification );
	}

	/**
	 * @covers ::is_valid_authentication_token
	 */
	public function test_valid_token_rejected_for_other_pages() {
		$verified = is_valid_authentication_token( self::$valid_action_page->ID, self::$valid_action, self::$valid_token['value'] );

		$this->assertSame( false, $verified );
	}

	/**
	 * @covers ::is_valid_authentication_token
	 */
	public function test_valid_token_rejected_for_other_actions() {
		// Setup another valid token for the other action.
		$other_valid_action = 'confirm_contributor_participation';
		// todo update this when the action for that step is created, so that they match and show that valid actions.
		$other_valid_action_url = get_authentication_url( self::$valid_pledge->ID, $other_valid_action, self::$valid_action_page->ID );

		// Intentionally mismatch the token and action.
		$verified = is_valid_authentication_token( self::$valid_pledge->ID, $other_valid_action, self::$valid_token['value'] );

		$this->assertSame( false, $verified );
	}

	/**
	 * @covers ::is_valid_authentication_token
	 */
	public function test_valid_token_rejected_for_other_pledge() {
		$other_valid_pledge_params = array(
			'post_type'   => PLEDGE_POST_TYPE,
			'post_title'  => 'Other Valid Pledge',
			'post_status' => 'publish',
		);

		$other_valid_pledge_id = self::factory()->post->create( $other_valid_pledge_params );
		$other_valid_pledge    = get_post( $other_valid_pledge_id );

		// Create a valid token for the other pledge.
		get_authentication_url( $other_valid_pledge->ID, self::$valid_action, self::$valid_action_page->ID );

		$other_valid_token = get_post_meta( $other_valid_pledge->ID, TOKEN_PREFIX . self::$valid_action, true );

		// Intentionally mismatch the pledge and token.
		$verified = is_valid_authentication_token( $other_valid_pledge_id, self::$valid_action, self::$valid_token['value'] );

		$this->assertSame( 'Other Valid Pledge', $other_valid_pledge->post_title );
		$this->assertSame( 'array', gettype( $other_valid_token ) );
		$this->assertArrayHasKey( 'value', $other_valid_token );
		$this->assertNotSame( $other_valid_token['value'], self::$valid_token['value'] );
		$this->assertSame( false, $verified );
	}

	/**
	 * @covers ::is_valid_authentication_token
	 */
	public function test_reusable_token_is_reusable() {
		$reusable_action = 'manage_pledge';
		get_authentication_url( self::$valid_pledge->ID, $reusable_action, self::$valid_action_page->ID, false );
		$reusable_token = get_post_meta( self::$valid_pledge->ID, TOKEN_PREFIX . $reusable_action, true );

		// The token should be usable multiple times.
		$first_verification  = is_valid_authentication_token( self::$valid_pledge->ID, $reusable_action, $reusable_token['value'] );
		$second_verification = is_valid_authentication_token( self::$valid_pledge->ID, $reusable_action, $reusable_token['value'] );
		$third_verification  = is_valid_authentication_token( self::$valid_pledge->ID, $reusable_action, $reusable_token['value'] );

		$this->assertSame( true, $first_verification );
		$this->assertSame( true, $second_verification );
		$this->assertSame( true, $third_verification );
	}

	/**
	 * @covers ::is_valid_authentication_token
	 */
	public function test_expired_reusable_token_rejected() {
		$reusable_action = 'manage_pledge';
		get_authentication_url( self::$valid_pledge->ID, $reusable_action, self::$valid_action_page->ID, false );
		$reusable_token = get_post_meta( self::$valid_pledge->ID, TOKEN_PREFIX . $reusable_action, true );

		$reusable_token['expiration'] = time() - 1;
		update_post_meta( self::$valid_pledge->ID, TOKEN_PREFIX . $reusable_action, $reusable_token );

		$verified = is_valid_authentication_token( self::$valid_pledge->ID, $reusable_action, $reusable_token['value'] );

		$this->assertSame( false, $verified );
	}
}
