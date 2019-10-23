<?php

/**
 * Helper functions for sending emails, including authentication tokens.
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
 * reusable, last up to 24 hours, and have a much smaller search space in brute force attacks. They also create an
 * inconsistent UX, because a token could be valid for 24 hours, or for 1 second, due to how `wp_nonce_tick()`
 * works. That would lead to some situations where a nonce had already expired by the time the contributor opened
 * the email and clicked on the link.
 *
 * So instead, true NONCEs are implemented; see `is_valid_authentication_token()` for details.
 */

namespace WordPressDotOrg\FiveForTheFuture\Email;

defined( 'WPINC' ) || die();

const TOKEN_PREFIX = '5ftf_auth_token_';

/**
 * Wrap `wp_mail()` with shared functionality.
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 *
 * @return bool
 */
function send_email( $to, $subject, $message ) {
	$headers = array(
		'From: WordPress - Five for the Future <donotreply@wordpress.org>',
		'Reply-To: support@wordcamp.org',
			// todo update address when new one is created
	);

	return wp_mail( $to, $subject, $message, $headers );
}

/**
 * Generate an action URL with a unique authentication token.
 *
 * @param int    $pledge_id
 * @param string $action
 * @param int    $action_page_id The ID of the page that the user will be taken back to, in order to process their
 *                               verification request.
 *
 * @return string
 */
function get_authentication_url( $pledge_id, $action, $action_page_id ) {
	$auth_token = array(
		'value'      => wp_generate_password( 20, false ), // Similar to `get_password_reset_key()`.
			// todo should encrypt at rest? core doesn't but others do
		'expiration' => time() + ( 2 * HOUR_IN_SECONDS ),
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
		//  probably, but what's the best way to do that DRYly?

	return $auth_url;
}

/**
 * Verify whether or not a given authentication token is valid.
 *
 * These tokens are more secure than WordPress' imitation nonces, because they can only be used once, and expire
 * in a shorter timeframe. Like WP nonces, though, they must be tied to a specific action and post object in order
 * to prevent misuse.
 *
 * @param $pledge_id
 * @param $action
 * @param $unverified_token
 *
 * @return bool
 */
function is_valid_authentication_token( $pledge_id, $action, $unverified_token ) {
	$verified    = false;
	$valid_token = get_post_meta( $pledge_id, TOKEN_PREFIX . $action, true );

	if ( $valid_token && $valid_token['expiration'] > time() && $unverified_token === $valid_token['value'] ) {
		$verified = true;

		// Tokens should not be reusable, to increase security.
		delete_post_meta( $pledge_id, TOKEN_PREFIX . $action );
		// todo when used to manage pledge, token will probably get deleted when viewing, and then they won't be able to save
			// fix that when create the manage process, though. for now this works for confirming email address.
			// maye pass a `context` param to this function, either 'view' or 'update', and only delete if context is 'update' ?
			// make sure view and update functions checks to make sure have valid token, not create though
	}

	return $verified;
}
