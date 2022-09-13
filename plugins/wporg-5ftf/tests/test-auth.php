<?php

use function WordPressDotOrg\FiveForTheFuture\Auth\{ can_manage_pledge, get_authentication_url, is_valid_authentication_token };
use const WordPressDotOrg\FiveForTheFuture\Auth\{ TOKEN_PREFIX };
use const WordPressDotOrg\FiveForTheFuture\Pledge\CPT_ID as PLEDGE_POST_TYPE;

defined( 'WPINC' ) || die();

/**
 * @group auth
 */
class Test_Auth extends WP_UnitTestCase {
	// phpcs:ignore PSR2.Classes.PropertyDeclaration.Multiple
	protected static $pledge, $action, $page, $action_url, $token;

	/**
	 * Setup fixtures that are shared across all tests.
	 */
	public static function set_up_before_class() {
		$pledge_id = self::factory()->post->create( array(
			'post_type'   => PLEDGE_POST_TYPE,
			'post_title'  => 'Valid Pledge',
			'post_status' => 'publish',
		) );

		$page_id = self::factory()->post->create( array(
			'post_type'   => 'page',
			'post_title'  => 'For Organizers',
			'post_status' => 'publish',
		) );

		self::$pledge = get_post( $pledge_id );
		self::$page   = get_post( $page_id );
		self::$action = 'confirm_pledge_email';

		self::verify_before_class_fixtures();
	}

	/**
	 * Verify whether or not the fixtures were setup correctly.
	 *
	 * @return void
	 */
	protected static function verify_before_class_fixtures() {
		self::assertSame( 'object',         gettype( self::$page ) );
		self::assertSame( 'For Organizers', self::$page->post_title );
		self::assertSame( 'object',         gettype( self::$pledge ) );
		self::assertSame( 'Valid Pledge',   self::$pledge->post_title );
		self::assertSame( PLEDGE_POST_TYPE, self::$pledge->post_type );
	}

	/**
	 * Setup fixtures that are unique for each test.
	 */
	public function set_up() : void {
		parent::set_up();

		/*
		 * `get_authentication_url()` should create a valid token in the database.
		 *
		 * This must be called before every test, because the process of verifying a valid token will delete it.
		 */
		self::$action_url = get_authentication_url( self::$pledge->ID, self::$action, self::$page->ID );
		self::$token      = get_post_meta( self::$pledge->ID, TOKEN_PREFIX . self::$action, true );

		// Verify that the fixtures are setup correctly.
		$action_url_args = wp_parse_args( wp_parse_url( self::$action_url, PHP_URL_QUERY ) );

		$this->assertSame( 'array', gettype( self::$token ) );
		$this->assertSame( $action_url_args['action'], self::$action );
		$this->assertSame( $action_url_args['auth_token'], self::$token['value'] );
	}

	/**
	 * Helper function to create & get a token.
	 */
	private function _get_token( $pledge_id, $action, $page_id, $single = true ) {
		get_authentication_url( $pledge_id, $action, $page_id, $single );
		return get_post_meta( $pledge_id, TOKEN_PREFIX . $action, true );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 */
	public function test_valid_token_accepted() {
		$verified = is_valid_authentication_token( self::$pledge->ID, self::$action, self::$token['value'] );
		$this->assertTrue( $verified );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 * @dataProvider data_invalid_token_provider
	 */
	public function test_invalid_tokens_are_rejected( $token_to_validate ) {
		$verified = is_valid_authentication_token(
			self::$pledge->ID,
			self::$action,
			$token_to_validate
		);

		$this->assertFalse( $verified );
	}

	/**
	 * Provide invalid tokens to be used by `test_invalid_tokens_are_rejected`.
	 *
	 * Note that data providers can't access fixtures.
	 * See https://phpunit.readthedocs.io/en/7.4/writing-tests-for-phpunit.html#data-providers.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 */
	public function data_invalid_token_provider() {
		return array(
			'non-existent-token' => array( false ), // Simulates `get_post_meta()` return value.
			'wrong-data-type'    => array( 'this string is not an array' ),
			'wrong-array-items'  => array( 'this' => "doesn't have `value` and `expiration` items" ),

			'invalid-value' => array(
				// Must have TOKEN_LENGTH characters, otherwise could be rejected for the wrong reason.
				'value'      => 'Has special characters !@#$%^&*)',
				'expiration' => time() + MINUTE_IN_SECONDS,
			),
		);
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 */
	public function test_expired_tokens_are_rejected() {
		$expired_token               = self::$token;
		$expired_token['expiration'] = time() - 1;

		update_post_meta( self::$pledge->ID, TOKEN_PREFIX . self::$action, $expired_token );

		$verified = is_valid_authentication_token(
			self::$pledge->ID,
			self::$action,
			self::$token['value']
		);

		$this->assertFalse( $verified );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 */
	public function test_used_tokens_are_rejected() {
		// The token should be deleted once it's used/verified for the first time.
		$first_verification  = is_valid_authentication_token( self::$pledge->ID, self::$action, self::$token['value'] );
		$second_verification = is_valid_authentication_token( self::$pledge->ID, self::$action, self::$token['value'] );

		$this->assertSame( true, $first_verification );
		$this->assertFalse( $second_verification );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 */
	public function test_valid_tokens_are_rejected_for_other_actions() {
		// Generate new token on pledge.
		$new_action = 'confirm_contributor_participation';
		self::_get_token( self::$pledge->ID, $new_action, self::$page->ID );

		// Intentionally mismatch the token and action.
		$verified = is_valid_authentication_token(
			self::$pledge->ID,
			$new_action,
			self::$token['value']
		);

		$this->assertFalse( $verified );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 */
	public function test_valid_tokens_are_rejected_for_other_pledges() {
		$new_pledge_id = self::factory()->post->create( array(
			'post_type'   => PLEDGE_POST_TYPE,
			'post_title'  => 'Other Valid Pledge',
			'post_status' => 'publish',
		) );

		$new_pledge = get_post( $new_pledge_id );
		$new_token  = self::_get_token( $new_pledge->ID, self::$action, self::$page->ID );

		// Intentionally mismatch the pledge and token.
		$verified = is_valid_authentication_token( $new_pledge_id, self::$action, self::$token['value'] );

		$this->assertSame( 'Other Valid Pledge', $new_pledge->post_title );
		$this->assertSame( 'array', gettype( $new_token ) );
		$this->assertArrayHasKey( 'value', $new_token );
		$this->assertNotSame( $new_token['value'], self::$token['value'] );
		$this->assertFalse( $verified );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 */
	public function test_reusable_token_is_reusable() {
		$action = 'manage_pledge';
		$token  = self::_get_token( self::$pledge->ID, $action, self::$page->ID, false );

		// The token should be usable multiple times.
		$first_verification  = is_valid_authentication_token( self::$pledge->ID, $action, $token['value'] );
		$second_verification = is_valid_authentication_token( self::$pledge->ID, $action, $token['value'] );
		$third_verification  = is_valid_authentication_token( self::$pledge->ID, $action, $token['value'] );

		$this->assertSame( true, $first_verification );
		$this->assertSame( true, $second_verification );
		$this->assertSame( true, $third_verification );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\is_valid_authentication_token
	 */
	public function test_expired_reusable_tokens_are_rejected() {
		$action = 'manage_pledge';
		$token  = self::_get_token( self::$pledge->ID, $action, self::$page->ID, false );

		$token['expiration'] = time() - 1;
		update_post_meta( self::$pledge->ID, TOKEN_PREFIX . $action, $token );

		$verified = is_valid_authentication_token( self::$pledge->ID, $action, $token['value'] );

		$this->assertFalse( $verified );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\can_manage_pledge
	 */
	public function test_user_with_token_can_manage_pledge() {
		$action = 'manage_pledge';
		$token  = self::_get_token( self::$pledge->ID, $action, self::$page->ID, false );

		$result = can_manage_pledge( self::$pledge->ID, $token['value'] );
		$this->assertTrue( $result );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\can_manage_pledge
	 */
	public function test_user_without_token_cant_manage_pledge() {
		$result = can_manage_pledge( self::$pledge->ID, '' );
		$this->assertWPError( $result );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\can_manage_pledge
	 */
	public function test_logged_in_admin_can_manage_pledge() {
		$user = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $user );

		$result = can_manage_pledge( self::$pledge->ID );
		$this->assertTrue( $result );
	}

	/**
	 * @covers WordPressDotOrg\FiveForTheFuture\Auth\can_manage_pledge
	 */
	public function test_logged_in_subscriber_cant_manage_pledge() {
		$user = self::factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);
		wp_set_current_user( $user );

		$result = can_manage_pledge( self::$pledge->ID );
		$this->assertWPError( $result );
	}
}
