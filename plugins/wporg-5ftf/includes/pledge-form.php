<?php
/**
 * Render and process the pledge forms.
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeForm;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Pledge, PledgeMeta, Contributor, Email };
use WP_Error, WP_User;

defined( 'WPINC' ) || die();

// Todo make this into simple optionless blocks instead?
add_shortcode( '5ftf_pledge_form_new', __NAMESPACE__ . '\render_form_new' );
add_shortcode( '5ftf_pledge_form_manage', __NAMESPACE__ . '\render_form_manage' );

/**
 * Render the form(s) for creating new pledges.
 *
 * @return false|string
 */
function render_form_new() {
	$action        = isset( $_GET['action'] ) ? filter_input( INPUT_GET, 'action' ) : filter_input( INPUT_POST, 'action' );
	$data          = get_form_submission();
	$messages      = [];
	$pledge        = null;
	$complete      = false;
	$directory_url = get_permalink( get_page_by_path( 'pledges' ) );
	$view          = 'form-pledge-new.php';

	if ( 'Submit Pledge' === $action ) {
		$pledge_id = process_form_new();

		if ( is_wp_error( $pledge_id ) ) {
			$messages = array_merge( $messages, $pledge_id->get_error_messages() );
		} elseif ( is_int( $pledge_id ) ) {
			$complete = true;
		}
	} elseif ( 'confirm_pledge_email' === $action ) {
		$view             = 'form-pledge-confirm-email.php';
		$pledge_id        = filter_input( INPUT_GET, 'pledge_id', FILTER_VALIDATE_INT );
		$unverified_token = filter_input( INPUT_GET, 'auth_token', FILTER_SANITIZE_STRING );
		$email_confirmed  = process_pledge_confirmation_email( $pledge_id, $action, $unverified_token );
		$pledge           = get_post( $pledge_id );
	} elseif ( filter_input( INPUT_GET, 'resend_pledge_confirmation' ) ) {
		$pledge_id = filter_input( INPUT_GET, 'pledge_id', FILTER_VALIDATE_INT );
		$complete  = true;

		Pledge\send_pledge_confirmation_email( $pledge_id, get_post()->ID );
	}

	ob_start();
	$readonly = false;
	require FiveForTheFuture\get_views_path() . $view;

	return ob_get_clean();
}

/**
 * Process a submission from the New Pledge form.
 *
 * @return int|WP_Error The post ID of the new pledge if the form processed correctly. Otherwise WP_Error.
 */
function process_form_new() {
	$submission = get_form_submission();
	$has_error  = check_invalid_submission( $submission );
	if ( $has_error ) {
		return $has_error;
	}

	$contributors = parse_contributors( $submission['pledge-contributors'] );
	if ( is_wp_error( $contributors ) ) {
		return $contributors;
	}

	$logo_attachment_id = upload_image( $_FILES['org-logo'] );
	if ( is_wp_error( $logo_attachment_id ) ) {
		return $logo_attachment_id;
	}

	$name = sanitize_meta(
		PledgeMeta\META_PREFIX . 'org-name',
		$submission['org-name'],
		'post',
		Pledge\CPT_ID
	);

	$new_pledge_id = Pledge\create_new_pledge( $name );

	if ( is_wp_error( $new_pledge_id ) ) {
		return $new_pledge_id;
	}

	Contributor\add_pledge_contributors( $new_pledge_id, $contributors );

	// Attach logo to the pledge.
	wp_update_post( array(
		'ID'          => $logo_attachment_id,
		'post_parent' => $new_pledge_id,
	) );
	set_post_thumbnail( $new_pledge_id, $logo_attachment_id );

	return $new_pledge_id;
}

/**
 * Process a request to confirm a company's email address.
 *
 * @param int    $pledge_id
 * @param string $action
 * @param array  $unverified_token
 *
 * @return bool
 */
function process_pledge_confirmation_email( $pledge_id, $action, $unverified_token ) {
	$meta_key          = PledgeMeta\META_PREFIX . 'pledge-email-confirmed';
	$already_confirmed = get_post( $pledge_id )->$meta_key;

	if ( $already_confirmed ) {
		/*
		 * If they refresh the page after confirming, they'd otherwise get an error because the token had been
		 * used, and might be confused and think that the address wasn't confirmed.
		 *
		 * This leaks the fact that the address is confirmed, because it will return true even if the token is
		 * invalid, but there aren't any security/privacy implications of that.
		 */
		return true;
	}

	$email_confirmed = Email\is_valid_authentication_token( $pledge_id, $action, $unverified_token );

	if ( $email_confirmed ) {
		update_post_meta( $pledge_id, $meta_key, true );
		wp_update_post( array( 'ID' => $pledge_id, 'post_status' => 'publish' ) );
		send_contributor_confirmation_emails( $pledge_id );
	}

	return $email_confirmed;
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
		 * This uses w.org login accounts instead of `Email\get_authentication_url()`, because the reasons for using
		 * tokens for pledges don't apply to contributors, accounts are more secure, and they provide a better UX
		 * because there's no expiration.
		 */
		$message =
			"Howdy $name, {$pledge->post_title} has created a Five for the Future pledge on WordPress.org and listed you as one of the contributors that they sponsor to contribute to the WordPress open source project. You can view their pledge at:" . "\n\n" .

			get_permalink( $pledge_id ) . "\n\n" .

			"To confirm that they're sponsoring your contributions, please review your pledges at:" . "\n\n" .

			get_permalink( get_page_by_path( 'my-pledges' ) ) . "\n\n" .

			"Please also update your WordPress.org profile to include the number of hours per week that you contribute, and the teams that you contribute to:\n\n" .

			"https://profiles.wordpress.org/me/profile/edit/group/5/\n\n" .

			"If they aren't sponsoring your contributions, then you can ignore this email, and you won't be listed on their pledge."
		;

		$user = get_user_by( 'login', $contributor->post_title );
		Email\send_email( $user->user_email, $subject, $message );
	}
}

/**
 * Render the form(s) for managing existing pledges.
 *
 * @return false|string
 */
function render_form_manage() {
	$action   = filter_input( INPUT_POST, 'action' );
	$messages = [];
	$updated  = false;

	// @todo Get pledge ID from somewhere.
	$data = PledgeMeta\get_pledge_meta();

	if ( 'Update Pledge' === $action ) {
		$processed = process_form_manage();

		if ( is_wp_error( $processed ) ) {
			$messages = array_merge( $messages, $processed->get_error_messages() );
		} elseif ( 'success' === $processed ) {
			$updated = true;
		}
	}

	ob_start();
	$readonly = false;
	require FiveForTheFuture\PATH . 'views/form-pledge-manage.php';

	return ob_get_clean();
}

/**
 * Process a submission from the Manage Existing Pledge form.
 *
 * TODO This doesn't actually update any data yet when the form is submitted.
 *
 * @return string|WP_Error String "success" if the form processed correctly. Otherwise WP_Error.
 */
function process_form_manage() {
	$submission = get_form_submission();
	$has_error = check_invalid_submission( $submission );
	if ( $has_error ) {
		return $has_error;
	}

	// todo email any new contributors for confirmation
	// notify any removed contributors?
		// ask them to update their profiles?
	// automatically update contributor profiles?
	// anything else?
}

/**
 * Get and sanitize $_POST values from a form submission.
 *
 * @return array|bool
 */
function get_form_submission() {
	$input_filters = array_merge(
		// Inputs that correspond to meta values.
		wp_list_pluck( PledgeMeta\get_pledge_meta_config( 'user_input' ), 'php_filter' ),
		// Inputs with no corresponding meta value.
		array(
			'pledge-contributors' => FILTER_SANITIZE_STRING,
			'pledge-agreement'    => FILTER_VALIDATE_BOOLEAN,
		)
	);

	return filter_input_array( INPUT_POST, $input_filters );
}

/**
 * Check a key value against existing pledges to see if one already exists.
 *
 * @param string $key               The value to match against other pledges.
 * @param string $key_type          The type of value being matched. `email` or `domain`.
 * @param int    $current_pledge_id Optional. The post ID of the pledge to compare against others.
 *
 * @return bool
 */
function has_existing_pledge( $key, $key_type, int $current_pledge_id = 0 ) {
	$args = array(
		'post_type'   => Pledge\CPT_ID,
		'post_status' => array( 'draft', 'pending', 'publish' ),
	);

	switch ( $key_type ) {
		case 'email':
			$args['meta_query'] = array(
				array(
					'key'   => PledgeMeta\META_PREFIX . 'org-pledge-email',
					'value' => $key,
				),
			);
			break;
		case 'domain':
			$args['meta_query'] = array(
				array(
					'key'   => PledgeMeta\META_PREFIX . 'org-domain',
					'value' => $key,
				),
			);
			break;
	}

	if ( $current_pledge_id ) {
		$args['exclude'] = array( $current_pledge_id );
	}

	$matching_pledge = get_posts( $args );

	return ! empty( $matching_pledge );
}

/**
 * Ensure each item in a list of usernames is valid and corresponds to a user.
 *
 * @param string $contributors A comma-separated list of username strings.
 *
 * @return array|WP_Error An array of sanitized wporg usernames on success. Otherwise WP_Error.
 */
function parse_contributors( $contributors ) {
	$invalid_contributors   = array();
	$sanitized_contributors = array();

	$contributors = str_replace( '@', '', $contributors );
	$contributors = explode( ',', $contributors );

	foreach ( $contributors as $wporg_username ) {
		$sanitized_username = sanitize_user( $wporg_username );
		$user               = get_user_by( 'login', $sanitized_username );

		if ( ! $user instanceof WP_User ) {
			$user = get_user_by( 'slug', $sanitized_username );
		}

		if ( $user instanceof WP_User ) {
			$sanitized_contributors[] = $user->user_login;
		} else {
			$invalid_contributors[] = $wporg_username;
		}
	}

	if ( ! empty( $invalid_contributors ) ) {
		/* translators: Used between sponsor names in a list, there is a space after the comma. */
		$item_separator = _x( ', ', 'list item separator', 'wporg' );

		return new WP_Error(
			'invalid_contributor',
			sprintf(
				/* translators: %s is a list of usernames. */
				__( 'The following contributor usernames are not valid: %s', 'wporg' ),
				implode( $item_separator, $invalid_contributors )
			)
		);
	}

	if ( empty( $sanitized_contributors ) ) {
		return new WP_Error(
			'contributor_required',
			__( 'The pledge must have at least one contributor username.', 'wporg' )
		);
	}

	$sanitized_contributors = array_unique( $sanitized_contributors );

	return $sanitized_contributors;
}

/**
 * Check the submission for valid data.
 *
 * @return false|WP_Error Return any errors in the submission, or false if no errors.
 */
function check_invalid_submission( $submission ) {
	$has_required = PledgeMeta\has_required_pledge_meta( $submission );
	if ( is_wp_error( $has_required ) ) {
		return $has_required;
	}

	$email = sanitize_meta(
		PledgeMeta\META_PREFIX . 'org-pledge-email',
		$submission['org-pledge-email'],
		'post',
		Pledge\CPT_ID
	);

	if ( has_existing_pledge( $email, 'email' ) ) {
		return new WP_Error(
			'existing_pledge_email',
			__( 'This email address is already connected to an existing pledge.', 'wporg' )
		);
	}

	$domain = PledgeMeta\get_normalized_domain_from_url( $submission['org-url'] );

	if ( has_existing_pledge( $domain, 'domain' ) ) {
		return new WP_Error(
			'existing_pledge_domain',
			__( 'A pledge already exists for this domain.', 'wporg' )
		);
	}

	return false;
}

/**
 * Upload the logo image into the media library.
 *
 * @param array $logo $_FILES array for the uploaded logo.
 * @return int|WP_Error Upload attachment ID, or WP_Error if there was an error.
 */
function upload_image( $logo ) {
	if ( ! $logo ) {
		return false;
	}

	// Process image.
	if ( ! function_exists('media_handle_upload') ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}

	if ( ! function_exists('check_upload_size') ) {
		require_once ABSPATH . 'wp-includes/ms-functions.php';
		require_once ABSPATH . 'wp-admin/includes/ms.php';
	}

	add_filter( 'upload_mimes', __NAMESPACE__ . '\safelist_image_mimes' );
	add_filter( 'pre_site_option_fileupload_maxk', __NAMESPACE__ . '\restrict_file_size' );
	add_filter( 'wp_handle_sideload_prefilter', 'check_upload_size' );

	$logo_id = \media_handle_sideload( $logo, 0 );

	remove_filter( 'upload_mimes', __NAMESPACE__ . '\safelist_image_mimes' );
	remove_filter( 'pre_site_option_fileupload_maxk', __NAMESPACE__ . '\restrict_file_size' );
	remove_filter( 'wp_handle_sideload_prefilter', 'check_upload_size' );

	return $logo_id;
}

/**
 * Only allow image mime types.
 *
 * @param array $mimes Mime types keyed by the file extension regex corresponding to those types.
 */
function safelist_image_mimes( $mimes ) {
	return array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif'          => 'image/gif',
		'png'          => 'image/png',
	);
}

/**
 * Restrict images uploaded by this form to be less than 5MB.
 *
 * @param bool $value Null– returning a value will short-circuit the option lookup.
 */
function restrict_file_size( $value ) {
	return 5 * MB_IN_BYTES;
}
