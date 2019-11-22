<?php
/**
 * Handle submissions to admin-ajax.php.
 */

namespace WordPressDotOrg\FiveForTheFuture\Endpoints;

use WordPressDotOrg\FiveForTheFuture\{ Auth, Contributor, Email };
use const WordPressDotOrg\FiveForTheFuture\PledgeMeta\META_PREFIX;

add_action( 'wp_ajax_manage-contributors',        __NAMESPACE__ . '\manage_contributors_handler' );
add_action( 'wp_ajax_nopriv_manage-contributors', __NAMESPACE__ . '\manage_contributors_handler' );

add_action( 'wp_ajax_send-manage-email',        __NAMESPACE__ . '\send_manage_email_handler' );
add_action( 'wp_ajax_nopriv_send-manage-email', __NAMESPACE__ . '\send_manage_email_handler' );

/**
 * Handle the AJAX request for managing contributors on a pledge.
 * This responds to adding, removing, and resending emails to contributors.
 */
function manage_contributors_handler() {
	check_ajax_referer( 'manage-contributors', '_ajax_nonce' );

	$action         = filter_input( INPUT_POST, 'manage_action' );
	$pledge_id      = filter_input( INPUT_POST, 'pledge_id', FILTER_VALIDATE_INT );
	$contributor_id = filter_input( INPUT_POST, 'contributor_id', FILTER_VALIDATE_INT );
	$token          = filter_input( INPUT_POST, '_token' );
	$authenticated  = Auth\can_manage_pledge( $pledge_id, $token );

	if ( is_wp_error( $authenticated ) ) {
		wp_die( wp_json_encode( [
			'success' => false,
			'message' => $authenticated->get_error_message(),
		] ) );
	}

	switch ( $action ) {
		case 'resend-contributor-confirmation':
			$contribution = get_post( $contributor_id );
			Email\send_contributor_confirmation_emails( $pledge_id, $contributor_id );
			wp_die( wp_json_encode( [
				'success' => true,
				'message' => sprintf( __( 'Confirmation email sent to %s.', 'wporg-5ftf' ), $contribution->post_title ),
			] ) );
			break;

		case 'remove-contributor':
			// Trash contributor.
			Contributor\remove_contributor( $contributor_id );
			wp_die( wp_json_encode( [
				'success'      => true,
				'contributors' => Contributor\get_pledge_contributors_data( $pledge_id ),
			] ) );
			break;

		case 'add-contributor':
			$pledge = get_post( $pledge_id );
			$new_contributors = Contributor\parse_contributors( $_POST['contributors'] );
			if ( is_wp_error( $new_contributors ) ) {
				wp_die( wp_json_encode( [
					'success' => false,
					'message' => $new_contributors->get_error_message(),
				] ) );
			}
			$contributor_ids = Contributor\add_pledge_contributors( $pledge_id, $new_contributors );
			if ( 'publish' === $pledge->post_status ) {
				foreach ( $contributor_ids as $contributor_id ) {
					Email\send_contributor_confirmation_emails( $pledge_id, $contributor_id );
				}
			}

			// Fetch all contributors, now that the new ones have been added.
			$contributors = Contributor\get_pledge_contributors_data( $pledge_id );

			wp_die( wp_json_encode( [
				'success'      => true,
				'contributors' => $contributors,
			] ) );
			break;
	}

	// No matching action, we can just exit.
	wp_die();
}

/**
 * Handle the AJAX request for managing a pledge.
 * This responds to a request for a pledge manage link.
 */
function send_manage_email_handler() {
	check_ajax_referer( 'send-manage-email', '_ajax_nonce' );

	$pledge_id   = filter_input( INPUT_POST, 'pledge_id', FILTER_VALIDATE_INT );
	$email       = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );
	$valid_email = get_post( $pledge_id )->{ META_PREFIX . 'org-pledge-email' };

	if ( $valid_email && $valid_email === $email ) {
		$message_sent = Email\send_manage_pledge_link( $pledge_id );

		if ( $message_sent ) {
			$result = [
				'success' => true,
				'message' => __( 'Thanks! We’ve emailed you a link you can open in order to update your pledge.', 'wporg-5ftf' ),
			];
		} else {
			$result = [
				'success' => false,
				'message' => __( 'There was an error while trying to send the email.', 'wporg-5ftf' ),
			];
		}
	} else {
		$error_message = sprintf(
			__( 'That’s not the address that we have for this pledge. If you don’t know the email associated with this pledge, <a href="%s">please contact us for help.</a>', 'wporg-5ftf' ),
			get_permalink( get_page_by_path( 'report' ) )
		);

		$result = [
			'success' => false,
			'message' => $error_message,
		];
	}

	wp_die( wp_json_encode( $result ) );
}
