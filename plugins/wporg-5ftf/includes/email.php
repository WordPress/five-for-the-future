<?php
/**
 * Helper functions for sending emails.
 */

namespace WordPressDotOrg\FiveForTheFuture\Email;

use WordPressDotOrg\FiveForTheFuture\{ Auth, Contributor };
use function WordPressdotorg\MU_Plugins\Helpers\{ natural_language_join };
use const WordPressDotOrg\FiveForTheFuture\PREFIX;
use const WordPressDotOrg\FiveForTheFuture\PledgeMeta\META_PREFIX;
use WP_Post, WP_Error;

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
function send_email( $to, $subject, $message, $pledge_id = false ) {
	$headers = array(
		'From: WordPress - Five for the Future <bounce@wordpress.org>',
		'Reply-To: getinvolved@wordpress.org',
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

/**
 * Email pledge manager to confirm their email address.
 *
 * @param int $pledge_id      The ID of the pledge.
 * @param int $action_page_id The ID of the page that the user will be taken back to, in order to process their
 *                            confirmation request.
 *
 * @return bool
 */
function send_pledge_confirmation_email( $pledge_id, $action_page_id ) {
	$pledge = get_post( $pledge_id );

	$message = sprintf(
		"Thanks for pledging your organization's time to contribute to the WordPress open source project! Please confirm this email address in order to publish your pledge:\n\n%s",
		Auth\get_authentication_url( $pledge_id, 'confirm_pledge_email', $action_page_id )
	);

	return send_email(
		$pledge->{'5ftf_org-pledge-email'},
		'Please confirm your email address',
		$message,
		$pledge_id
	);
}

/**
 * Send contributors an email to confirm their participation.
 *
 * @param int      $pledge_id
 * @param int|null $contributor_id Optional. Send to a specific contributor instead of all.
 */
function send_contributor_confirmation_emails( $pledge_id, $contributor_id = null ) {
	$pledge  = get_post( $pledge_id );
	$subject = "Confirm your {$pledge->post_title} sponsorship";

	/*
	 * Only fetch unconfirmed ones, because we might be resending confirmation emails, and we shouldn't resend to
	 * confirmed contributors.
	 */
	$unconfirmed_contributors = Contributor\get_pledge_contributors( $pledge->ID, 'pending', $contributor_id );

	foreach ( $unconfirmed_contributors as $contributor ) {
		$user = get_user_by( 'login', $contributor->post_title );

		/*
		 * Their first name is ideal, but their username is the best fallback because `nickname`, `display_name`,
		 * etc are too formal.
		 */
		$name = $user->first_name ? $user->first_name : '@' . $user->user_nicename;

		/*
		 * This uses w.org login accounts instead of `Auth\get_authentication_url()`, because the reasons for using
		 * tokens for pledges don't apply to contributors, accounts are more secure, and they provide a better UX
		 * because there's no expiration.
		 */
		$message =
			"Howdy $name, {$pledge->post_title} has created a Five for the Future pledge on WordPress.org and listed you as one of the contributors that they sponsor to contribute to the WordPress open source project. You can view their pledge at:\n\n" .

			get_permalink( $pledge_id ) . "\n\n" .

			"To confirm that they're sponsoring your contributions, please review your pledges at:\n\n" .

			get_permalink( get_page_by_path( 'my-pledges' ) ) . "\n\n" .

			"Please also update your WordPress.org profile to include the number of hours per week that you contribute, and the teams that you contribute to:\n\n" .

			"https://profiles.wordpress.org/me/profile/edit/group/5/\n\n" .

			"If {$pledge->post_title} isn't sponsoring your contributions, then you can ignore this email, and you won't be listed on their pledge.";

		send_email( $user->user_email, $subject, $message, $pledge_id );
	}
}

/**
 * Send the removed contributor an email to notify them after removal.
 *
 * @param int     $pledge_id
 * @param WP_Post $contributor
 */
function send_contributor_removed_email( $pledge_id, $contributor ) {
	$pledge   = get_post( $pledge_id );
	$subject  = "Removed from {$pledge->post_title} Five for the Future pledge";
	$user     = get_user_by( 'login', $contributor->post_title );

	$message = sprintf( '
		Howdy %1$s,

		This email is to notify you that your WordPress.org contributor profile is no longer linked to %2$s’s Five for the Future pledge. If this is unexpected news, it’s best to reach out directly to %2$s with questions.

		If they were the only sponsor linked to your account, then the "Hours Per Week" and "Contributor Teams" fields on your profile have been reset, so that teams have accurate data. If you still plan on contributing without sponsorship, please revisit your profile and enter your new hours and teams.

		https://profiles.wordpress.org/me/profile/edit/group/5/

		Have a great day!',
		$contributor->post_title,
		$pledge->post_title
	);
	$message = str_replace( "\t", '', trim( $message ) );

	send_email( $user->user_email, $subject, $message, $pledge_id );
}

/**
 * Email the pledge admin a temporary link they can use to manage their pledge.
 *
 * @param int $pledge_id
 *
 * @return true|WP_Error
 */
function send_manage_pledge_link( $pledge_id ) {
	$admin_email = get_post( $pledge_id )->{ META_PREFIX . 'org-pledge-email' };

	if ( ! is_email( $admin_email ) ) {
		return new WP_Error( 'invalid_email', 'Invalid email address.' );
	}

	$subject = __( 'Next steps to update your Pledge', 'wporg-5ftf' );
	$message = sprintf(
		"Hi there!\n\nThanks for confirming your email! Here is the link to update your pledge: \n\n%s\n\nIf you have any questions about how to update your pledge, or about Five for the Future in general, please visit %s.\n\nThanks for participating in Five for the Future. Your contribution ensures that WordPress continues to thrive!",
		Auth\get_authentication_url(
			$pledge_id,
			'manage_pledge',
			get_page_by_path( 'manage-pledge' )->ID,
			// The token needs to be reused so that the admin can view the form, submit it, and view the result.
			false
		),
		home_url()
	);

	$result = send_email( $admin_email, $subject, $message, $pledge_id );

	if ( ! $result ) {
		$result = new WP_Error( 'email_failed', 'Email failed to send' );
	}

	return $result;
}

/**
 * Email pledge manager to notify that the pledge has been removed.
 *
 * @param WP_Post $pledge The pledge object, used to add the title now that the pledge itself has been deleted.
 *
 * @return bool
 */
function send_pledge_deactivation_email( $pledge ) {
	$message = sprintf(
		"Your organization, %s, has been removed from the Five for the Future listing.\n\n" .
		'Please reply to this email if this was a mistake.',
		$pledge->post_title
	);

	return send_email(
		$pledge->{'5ftf_org-pledge-email'},
		'Pledge removed from Five for the Future',
		$message,
		$pledge->ID
	);
}

/**
 * Ask an inactive contributor to update their pledge for accuracy.
 */
function send_contributor_inactive_email( array $contributor ) : bool {
	/*
	 * Their first name is ideal, but their username is the best fallback because `nickname`, `display_name`,
	 * etc are too formal.
	 */
	$name    = empty( $contributor['first_name'] ) ? '@' . $contributor['user_nicename'] : $contributor['first_name'];
	$subject = 'Please update your Five for the Future pledge';

	$short_team_names = array_map( function( $team ) {
		$team = str_replace( 'Team', '', $team );

		return trim( $team );
	}, $contributor['teams_names'] );

	$message = sprintf( "
		Hi %s, a while ago you pledged to contribute %d %s a week to the %s %s:

		https://profiles.wordpress.org/%s

		We haven't seen you in a while, and we need your help! We're working on some new and important projects. If you'd like to get involved, or have any questions, you can:

		* Talk to your team in their Slack channel at https://wordpress.slack.com. If you need to create a Slack account, you can do that at https://chat.wordpress.org. You can find your team representatives at https://make.wordpress.org/updates/team-reps/.

		* Read your team's handbook: Visit https://make.wordpress.org, select your team, and open the Handbook link in the menu.

		* Respond to this email.

		If you're no longer able to contribute to the WordPress project, that's ok, we understand! Please update your pledge to reflect your current contributions, so that team representatives know who is available to help.

		https://profiles.wordpress.org/me/profile/edit/group/5/

		If you have been contributing, but it isn't showing up in the Activity section of your profile, please reply and let us know so we can work on crediting that in the future.

		Thanks for your pledge, we look forward to working with you on the future of WordPress.

		Have a great day!",
		$name,
		$contributor['hours_per_week'],
		1 === $contributor['hours_per_week'] ? 'hour' : 'hours',
		natural_language_join( $short_team_names ),
		1 === count( $contributor['teams_names'] ) ? 'team' : 'teams',
		$contributor['user_nicename']
	);
	$message = str_replace( "\t", '', trim( $message ) );

	return send_email( $contributor['user_email'], $subject, $message );
}
