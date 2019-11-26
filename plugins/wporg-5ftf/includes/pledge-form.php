<?php
/**
 * Render and process the pledge forms.
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeForm;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Auth, Contributor, Email, Pledge, PledgeMeta };
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
	$pledge_id     = 0;
	$data          = get_form_submission();
	$errors        = [];
	$pledge        = null;
	$complete      = false;
	$directory_url = home_url( 'pledges' );
	$view          = 'form-pledge-new.php';

	if ( 'Submit Pledge' === $action ) {
		$pledge_id = process_form_new();

		if ( is_wp_error( $pledge_id ) ) {
			$errors = array_merge( $errors, $pledge_id->get_error_messages() );
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

		Email\send_pledge_confirmation_email( $pledge_id, get_post()->ID );
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
	$has_error  = check_invalid_submission( $submission, 'create' );
	if ( $has_error ) {
		return $has_error;
	}

	$contributors = Contributor\parse_contributors( $submission['pledge-contributors'] );
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

	$email_confirmed = Auth\is_valid_authentication_token( $pledge_id, $action, $unverified_token );

	if ( $email_confirmed ) {
		update_post_meta( $pledge_id, $meta_key, true );
		wp_update_post( array(
			'ID'          => $pledge_id,
			'post_status' => 'publish',
		) );
		Email\send_contributor_confirmation_emails( $pledge_id );
	}

	return $email_confirmed;
}

/**
 * Render the form(s) for managing existing pledges.
 *
 * @return false|string
 */
function render_form_manage() {
	/*
	 * Prevent Gutenberg from executing this on the Edit Post screen.
	 * See https://github.com/WordPress/gutenberg/issues/18394
	 */
	if ( is_admin() ) {
		return '';
	}

	$messages = [];
	$errors   = [];

	$action        = sanitize_text_field( $_REQUEST['action'] ?? '' );
	$pledge_id     = absint( $_REQUEST['pledge_id'] ?? 0 );
	$auth_token    = sanitize_text_field( $_REQUEST['auth_token'] ?? '' );
	$can_view_form = Auth\can_manage_pledge( $pledge_id, $auth_token );

	if ( is_wp_error( $can_view_form ) ) {
		// Can't manage pledge, only show errors.
		$errors = array( $can_view_form->get_error_message() );

		ob_start();
		require FiveForTheFuture\PATH . 'views/partial-result-messages.php';
		return ob_get_clean();
	}

	if ( 'Update Pledge' === $action ) {
		$results = process_form_manage( $pledge_id, $auth_token );

		if ( is_wp_error( $results ) ) {
			$errors = $results->get_error_messages();
		} else {
			$messages = array( __( 'Your pledge has been updated.', 'wporg-5ftf' ) );
		}
	}

	$data         = PledgeMeta\get_pledge_meta( $pledge_id );
	$contributors = Contributor\get_pledge_contributors_data( $pledge_id );

	ob_start();
	$readonly = false;
	$is_manage = true;
	require FiveForTheFuture\PATH . 'views/form-pledge-manage.php';

	return ob_get_clean();
}

/**
 * Process a submission from the Manage Pledge form.
 *
 * @return WP_Error|true An error if the pledge could not be saved. Otherwise true.
 */
function process_form_manage( $pledge_id, $auth_token ) {
	$errors          = array();
	$nonce           = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$nonce_action    = 'manage_pledge_' . $pledge_id;
	$has_valid_nonce = wp_verify_nonce( $nonce, $nonce_action );

	/*
	 * This should be redundant, since it's also called by `render_form_manage()`, but it's good to also do it here
	 * just in case other code changes in the future, or this gets called by another flow, etc.
	 */
	$can_view_form = Auth\can_manage_pledge( $pledge_id, $auth_token );

	if ( ! $has_valid_nonce || is_wp_error( $can_view_form ) ) {
		return new WP_Error(
			'invalid_token',
			sprintf(
				__( 'Your link has expired, please <a href="%s">obtain a new one</a>.', 'wporg-5ftf' ),
				get_permalink( $pledge_id )
			)
		);
	}

	$submission = get_form_submission();
	$has_error = check_invalid_submission( $submission, 'update' );
	if ( $has_error ) {
		return $has_error;
	}

	PledgeMeta\save_pledge_meta( $pledge_id, $submission );

	if ( isset( $_FILES['org-logo'], $_FILES['org-logo']['tmp_name'] ) && ! empty( $_FILES['org-logo']['tmp_name'] ) ) {
		$current_logo_id    = get_post_thumbnail_id( $pledge_id );
		$logo_attachment_id = upload_image( $_FILES['org-logo'] );
		if ( is_wp_error( $logo_attachment_id ) ) {
			return $logo_attachment_id;
		}

		// Attach new logo to the pledge.
		wp_update_post( array(
			'ID'          => $logo_attachment_id,
			'post_parent' => $pledge_id,
		) );
		$updated = set_post_thumbnail( $pledge_id, $logo_attachment_id );

		// Trash the old logo.
		if ( $updated ) {
			wp_delete_attachment( $current_logo_id );
		}
	}

	// @todo Save contributors.

	// If we made it to here, we've successfully saved the pledge.
	return true;
}

/**
 * Get and sanitize $_POST values from a form submission.
 *
 * @return array
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

	$result = filter_input_array( INPUT_POST, $input_filters );
	if ( ! $result ) {
		$result = array_fill_keys( array_keys( $input_filters ), '' );
		$result['empty_post'] = true;
	}

	return $result;
}

/**
 * Check the submission for valid data.
 *
 * @param array  $submission The user input.
 * @param string $context    Whether this is a new pledge (`create`) or an edit to an existing one (`update`).
 *
 * @return false|WP_Error Return any errors in the submission, or false if no errors.
 */
function check_invalid_submission( $submission, $context ) {
	$has_required = PledgeMeta\has_required_pledge_meta( $submission, $context );
	if ( is_wp_error( $has_required ) ) {
		return $has_required;
	}

	$email = sanitize_meta(
		PledgeMeta\META_PREFIX . 'org-pledge-email',
		$submission['org-pledge-email'],
		'post',
		Pledge\CPT_ID
	);

	if ( 'create' === $context ) {
		if ( Pledge\has_existing_pledge( $email, 'email' ) ) {
			return new WP_Error(
				'existing_pledge_email',
				__( 'This email address is already connected to an existing pledge.', 'wporg-5ftf' )
			);
		}

		$domain = PledgeMeta\get_normalized_domain_from_url( $submission['org-url'] );

		if ( Pledge\has_existing_pledge( $domain, 'domain' ) ) {
			return new WP_Error(
				'existing_pledge_domain',
				__( 'A pledge already exists for this domain.', 'wporg-5ftf' )
			);
		}
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
