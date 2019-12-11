<?php
/**
 * Helper functions creating & storing authentication tokens.
 *
 * We don't want pledges connected to individual w.org accounts, because that would encourage people to create
 * "company" accounts instead of having their contributions show up as real human beings; or because that
 * individual will likely eventually leave the company, and "ownership" of the pledge would be orphaned; or
 * because we'd have to tie multiple accounts to the pledge (and all the extra time/UX costs associated with that),
 * and would still have problems with orphaned ownership, etc.
 *
 * So instead, we just ask companies to create pledges using a group email (e.g., support@wordcamp.org), and
 * we email them time-restricted, once-time-use auth tokens when they want to "log in".
 *
 * WP "nonces" aren't ideal for this purpose from a security perspective, because they're less secure. They're
 * reusable, last up to 24 hours, and have a much smaller search space in brute force attacks. They're only
 * intended to prevent CSRF, and should not be used for authentication or authorization.
 *
 * They also create an inconsistent UX, because a nonce could be valid for 24 hours, or for 1 second, due to their
 * stateless nature -- see `wp_nonce_tick()`. That would lead to some situations where a nonce had already expired
 * by the time the contributor opened the email and clicked on the link.
 *
 * So instead, true stateful CSPRN authentication tokens are generated; see `get_authentication_url()` and
 * `is_valid_authentication_token()` for details.
 *
 * For additional background:
 * - https://stackoverflow.com/a/35715087/450127 (which is better security advice than ircmarxell's 2010 answer).
 */

namespace WordPressDotOrg\FiveForTheFuture\Auth;
use WP_Error;

defined( 'WPINC' ) || die();

const TOKEN_PREFIX = '5ftf_auth_token_';

// Longer than `get_password_reset_key()` just to be safe.
// See https://core.trac.wordpress.org/ticket/43546#comment:34.
const TOKEN_LENGTH = 32;

add_action( 'wp_head', __NAMESPACE__ . '\prevent_caching_auth_tokens', 99 );

/**
 * Prevent caching mechanisms from caching authentication tokens.
 *
 * Search engines would often be too slow to index tokens before they expire, but other mechanisms like Varnish,
 * etc could create situations where they're leaked to others.
 */
function prevent_caching_auth_tokens() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce not required, not processing form data.
	if ( isset( $_GET['auth_token'] ) || isset( $_POST['auth_token'] ) ) {
		nocache_headers();
	}
}

/**
 * Generate an action URL with a secure, unique authentication token.
 *
 * @param int    $pledge_id
 * @param string $action
 * @param int    $action_page_id The ID of the page that the user will be taken back to, in order to process their
 *                               confirmation request.
 * @param bool   $use_once       Whether or not the token should be deleted after the first use. Only pass `false`
 *                               when the action requires several steps in a flow, rather than a single step. For
 *                               instance, be able to 1) view a private pledge; 2) make changes and save them; and
 *                               3) reload the private pledge with the new changes displayed.
 *
 * @return string
 */
function get_authentication_url( $pledge_id, $action, $action_page_id, $use_once = true ) {
	$auth_token = array(
		// This will create a CSPRN and is similar to how `get_password_reset_key()` and
		// `generate_recovery_mode_token()` work.
		'value'      => wp_generate_password( TOKEN_LENGTH, false ),
		// todo Ideally should encrypt at rest, see https://core.trac.wordpress.org/ticket/24783.
		'expiration' => time() + ( 2 * HOUR_IN_SECONDS ),
		'use_once'   => $use_once,
	);

	/*
	 * Tying the token to a specific pledge is important for security, otherwise companies could get a valid token
	 * for their pledge, and use it to edit other company's pledges.
	 *
	 * Similarly, tying it to specific actions is also important, to protect against CSRF attacks.
	 *
	 * This function intentionally requires the caller to pass in a pledge ID and action, so that it can guarantee
	 * that each token will be unique across pledges and actions.
	 */
	update_post_meta( $pledge_id, TOKEN_PREFIX . $action, $auth_token );

	$auth_url = add_query_arg(
		array(
			'action'     => $action,
			'pledge_id'  => $pledge_id,
			'auth_token' => $auth_token['value'],
		),
		get_permalink( $action_page_id )
	);

	// todo include a "this lnk will expire in 10 hours and after its used once" message too?
		// probably, but what's the best way to do that DRYly?

	return $auth_url;
}

/**
 * Verify whether or not a given authentication token is valid.
 *
 * These tokens are more secure than WordPress' imitation nonces because they cannot be reused[1], and expire
 * in a shorter timeframe. Like WP nonces, though, they must be tied to a specific action and post object in order
 * to prevent misuse.
 *
 * [1] In some cases, tokens can be reused, when that is explicitly required for their flow. For an example, see
 * the documentation in `get_authentication_url()`.
 *
 * @param int    $pledge_id
 * @param string $action
 * @param string $unverified_token
 *
 * @return bool
 */
function is_valid_authentication_token( $pledge_id, $action, $unverified_token ) {
	$verified    = false;
	$valid_token = get_post_meta( $pledge_id, TOKEN_PREFIX . $action, true );

	/*
	 * Later on we'll compare the value to user input, and the user could input null/false/etc, so let's guarantee
	 * that the thing we're comparing against is really what we expect it to be.
	 */
	if ( ! is_array( $valid_token ) || ! array_key_exists( 'value', $valid_token ) || ! array_key_exists( 'expiration', $valid_token ) ) {
		return false;
	}

	if ( ! is_string( $valid_token['value'] ) || TOKEN_LENGTH !== strlen( $valid_token['value'] ) ) {
		return false;
	}

	if ( ! is_string( $unverified_token ) || TOKEN_LENGTH !== strlen( $unverified_token ) ) {
		return false;
	}

	if ( $valid_token && $valid_token['expiration'] > time() && hash_equals( $valid_token['value'], $unverified_token ) ) {
		$verified = true;

		// Tokens should not be reusable -- to increase security -- unless explicitly required to fulfill their purpose.
		if ( false !== $valid_token['use_once'] ) {
			delete_post_meta( $pledge_id, TOKEN_PREFIX . $action );
		}
	}

	return $verified;
}

/**
 * Checks user capabilities or auth token to see if this user can edit the given pledge.
 *
 * @param int    $requested_pledge_id The pledge to edit.
 * @param string $auth_token          The supplied auth token to check.
 *
 * @return true|WP_Error
 */
function can_manage_pledge( $requested_pledge_id, $auth_token = '' ) {
	// A valid token supersedes other auth methods.
	if ( true === is_valid_authentication_token( $requested_pledge_id, 'manage_pledge', $auth_token ) ) {
		return true;
	} else if ( is_user_logged_in() ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'invalid_token',
			sprintf(
				__( 'You don\'t have permissions to edit this page. <a href="%s">Request an edit link.</a>', 'wporg-5ftf' ),
				get_permalink( $requested_pledge_id )
			)
		);
	}

	return new WP_Error(
		'invalid_token',
		__( 'Your link has expired.', 'wporg-5ftf' )
	);
}

