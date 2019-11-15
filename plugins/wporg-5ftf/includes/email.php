<?php
/**
 * Helper functions for sending emails.
 */

namespace WordPressDotOrg\FiveForTheFuture\Email;

use const WordPressDotOrg\FiveForTheFuture\PREFIX;

defined( 'WPINC' ) || die();

/**
 * Wrap `wp_mail()` with shared functionality.
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param int    $pledge_id
 *
 * @return bool
 */
function send_email( $to, $subject, $message, $pledge_id ) {
	$headers = array(
		'From: WordPress - Five for the Future <donotreply@wordpress.org>',
		'Reply-To: support@wordcamp.org',
	);

	$result = wp_mail( $to, $subject, $message, $headers );

	/**
	 * Broadcast the results of an attempt to send an email.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param array  $headers
	 * @param bool   $result
	 * @param int    $pledge_id
	 */
	do_action( PREFIX . '_email_result', $to, $subject, $message, $headers, $result, $pledge_id );

	return $result;
}

