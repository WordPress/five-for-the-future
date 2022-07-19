<?php
/**
 * Render and process the pledge forms.
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeForm;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Auth, Contributor, Email, Pledge, PledgeMeta };
use WP_Error;

defined( 'WPINC' ) || die();

// Todo make this into simple optionless blocks instead?
add_shortcode( '5ftf_pledge_form_new', __NAMESPACE__ . '\render_form_new' );
add_shortcode( '5ftf_pledge_form_manage', __NAMESPACE__ . '\render_form_manage' );

// Short-circuit out of both shortcodes for shared functionality (confirming admin email, resending the pledge
// confirmation email).
add_filter( 'pre_do_shortcode_tag', __NAMESPACE__ . '\process_confirmed_email', 10, 2 );
add_filter( 'pre_do_shortcode_tag', __NAMESPACE__ . '\process_resend_confirm_email', 10, 2 );

/**
 * Render the form(s) for creating new pledges.
 *
 * @return false|string
 */
function render_form_new() {
	$action        = isset( $_GET['action'] ) ? filter_input( INPUT_GET, 'action' ) : filter_input( INPUT_POST, 'action' );
	$is_manage     = false;
	$pledge_id     = 0;
	$data          = get_form_submission();
	$errors        = array();
	$pledge        = null;
	$complete      = false;
	$directory_url = home_url( 'pledges' );

	if ( 'Submit Pledge' === $action ) {
		$pledge_id = process_form_new();

		if ( is_wp_error( $pledge_id ) ) {
			$errors = array_merge( $errors, $pledge_id->get_error_messages() );
		} elseif ( is_int( $pledge_id ) ) {
			$complete = true;
		}
	}

	ob_start();
	$readonly = false;
	require FiveForTheFuture\get_views_path() . 'form-pledge-new.php';

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

	$messages = array();
	$errors   = array();

	$action        = sanitize_text_field( $_REQUEST['action'] ?? '' );
	$pledge_id     = absint( $_REQUEST['pledge_id'] ?? 0 );
	$auth_token    = sanitize_text_field( $_REQUEST['auth_token'] ?? '' );
	$can_view_form = Auth\can_manage_pledge( $pledge_id, $auth_token );

	if ( is_wp_error( $can_view_form ) ) {
		$errors = array( strip_tags( $can_view_form->get_error_message() ) );
	} elseif ( ! Pledge\is_active_pledge( $pledge_id ) ) {
		$errors = array(
			sprintf(
				__( 'This pledge has been removed from Five for the Future. If this was a mistake, please <a href="%s">contact us</a> to reactivate your pledge.', 'wporg-5ftf' ),
				get_permalink( get_page_by_path( 'report' ) )
			),
		);
	}

	if ( Pledge\is_active_pledge( $pledge_id ) && is_wp_error( $can_view_form ) ) {
		ob_start();
		require FiveForTheFuture\get_views_path() . 'partial-request-manage-link.php';
		return ob_get_clean();
	}

	if ( count( $errors ) > 0 ) {
		ob_start();
		require FiveForTheFuture\get_views_path() . 'partial-result-messages.php';
		return ob_get_clean();
	}

	if ( 'remove-pledge' === $action ) {
		$results = process_form_remove( $pledge_id, $auth_token );

		if ( is_wp_error( $results ) ) {
			$errors = $results->get_error_messages();
		} else {
			$messages = array(
				sprintf(
					__( 'Your pledge has been removed. If this was a mistake, please <a href="%s">contact us</a> to reactivate your pledge.', 'wporg-5ftf' ),
					get_permalink( get_page_by_path( 'report' ) )
				),
			);
		}

		ob_start();
		require FiveForTheFuture\get_views_path() . 'partial-result-messages.php';
		return ob_get_clean();
	} elseif ( 'Update Pledge' === $action ) {
		$results = process_form_manage( $pledge_id, $auth_token );

		if ( is_wp_error( $results ) ) {
			$errors = $results->get_error_messages();
		} else {
			$messages = array( __( 'Your pledge has been updated.', 'wporg-5ftf' ) );

			$meta_key = PledgeMeta\META_PREFIX . 'pledge-email-confirmed';
			if ( ! get_post( $pledge_id )->$meta_key ) {
				$messages[] = __( 'You must confirm your new email address before it will be visible.', 'wporg-5ftf' );
			}
		}
	}

	$data         = PledgeMeta\get_pledge_meta( $pledge_id );
	$contributors = Contributor\get_pledge_contributors_data( $pledge_id );

	ob_start();
	$readonly  = false;
	$is_manage = true;
	require FiveForTheFuture\get_views_path() . 'form-pledge-manage.php';

	return ob_get_clean();
}

/**
 * Process a submission from the Manage Pledge form.
 *
 * @return WP_Error|true An error if the pledge could not be saved. Otherwise true.
 */
function process_form_manage( $pledge_id, $auth_token ) {
	$errors          = array();
	$nonce           = filter_input( INPUT_POST, '_wpnonce', FILTER_UNSAFE_RAW );
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
	$has_error  = check_invalid_submission( $submission, 'update' );
	if ( $has_error ) {
		return $has_error;
	}

	PledgeMeta\save_pledge_meta( $pledge_id, $submission );

	if ( isset( $_FILES['org-logo'], $_FILES['org-logo']['tmp_name'] ) && ! empty( $_FILES['org-logo']['tmp_name'] ) ) {
		$original_logo_id   = get_post_thumbnail_id( $pledge_id );
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
			$orig_logo_attachment = get_post( $original_logo_id );
			if ( $orig_logo_attachment && $pledge_id === $orig_logo_attachment->post_parent ) {
				wp_delete_attachment( $original_logo_id );
			}
		}
	}

	// If we made it to here, we've successfully saved the pledge.
	return true;
}

/**
 * Process a submission from the Remove Pledge form.
 *
 * @return WP_Error|true An error if the pledge could not be saved. Otherwise true.
 */
function process_form_remove( $pledge_id, $auth_token ) {
	$errors          = array();
	$nonce           = filter_input( INPUT_POST, '_wpnonce', FILTER_UNSAFE_RAW );
	$nonce_action    = 'remove_pledge_' . $pledge_id;
	$has_valid_nonce = wp_verify_nonce( $nonce, $nonce_action );
	$can_view_form   = Auth\can_manage_pledge( $pledge_id, $auth_token );

	if ( ! $has_valid_nonce || is_wp_error( $can_view_form ) ) {
		return new WP_Error(
			'invalid_token',
			sprintf(
				__( 'Your link has expired, please <a href="%s">obtain a new one</a>.', 'wporg-5ftf' ),
				get_permalink( $pledge_id )
			)
		);
	}

	$result = Pledge\deactivate( $pledge_id, true, 'Organization admin deactivated via Manage form.' );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	// If we made it to here, we've successfully removed the pledge.
	return true;
}

/**
 * Process a request to confirm a company's email address.
 *
 * @param string|false $value Short-circuit return value.
 * @param string       $tag   Shortcode name.
 *
 * @return bool|string
 */
function process_confirmed_email( $value, $tag ) {
	if ( ! in_array( $tag, array( '5ftf_pledge_form_new', '5ftf_pledge_form_manage' ), true ) ) {
		return $value;
	}

	$action = sanitize_text_field( $_REQUEST['action'] ?? '' );
	if ( 'confirm_pledge_email' !== $action ) {
		return $value;
	}

	$pledge_id  = filter_input( INPUT_GET, 'pledge_id', FILTER_VALIDATE_INT );
	$auth_token = filter_input( INPUT_GET, 'auth_token', FILTER_UNSAFE_RAW );

	$meta_key          = PledgeMeta\META_PREFIX . 'pledge-email-confirmed';
	$already_confirmed = get_post( $pledge_id )->$meta_key;
	$email_confirmed   = false;
	$is_new_pledge     = '5ftf_pledge_form_new' === $tag;

	if ( $already_confirmed ) {
		/*
		 * If they refresh the page after confirming, they'd otherwise get an error because the token had been
		 * used, and might be confused and think that the address wasn't confirmed.
		 *
		 * This leaks the fact that the address is confirmed, because it will return true even if the token is
		 * invalid, but there aren't any security/privacy implications of that.
		 */
		$email_confirmed = true;
	}

	$email_confirmed = Auth\is_valid_authentication_token( $pledge_id, $action, $auth_token );

	if ( $email_confirmed ) {
		update_post_meta( $pledge_id, $meta_key, true );
		wp_update_post( array(
			'ID'          => $pledge_id,
			'post_status' => 'publish',
		) );
		if ( $is_new_pledge ) {
			Email\send_contributor_confirmation_emails( $pledge_id );
		}
	}

	ob_start();
	$directory_url = home_url( 'pledges' );
	$pledge        = get_post( $pledge_id );
	require FiveForTheFuture\get_views_path() . 'form-pledge-confirm-email.php';
	return ob_get_clean();
}

/**
 * Process a request to resed a company's confirmation email.
 *
 * @param string|false $value Short-circuit return value.
 * @param string       $tag   Shortcode name.
 *
 * @return bool|string
 */
function process_resend_confirm_email( $value, $tag ) {
	if ( ! in_array( $tag, array( '5ftf_pledge_form_new', '5ftf_pledge_form_manage' ), true ) ) {
		return $value;
	}

	$action = sanitize_text_field( $_REQUEST['action'] ?? '' );
	if ( 'resend_pledge_confirmation' !== $action ) {
		return $value;
	}

	$pledge_id = filter_input( INPUT_GET, 'pledge_id', FILTER_VALIDATE_INT );
	Email\send_pledge_confirmation_email( $pledge_id, get_post()->ID );

	$messages = array(
		sprintf(
			__( 'We’ve emailed you a new link to confirm your address for %s.', 'wporg-5ftf' ),
			get_the_title( $pledge_id )
		),
	);

	ob_start();
	require FiveForTheFuture\get_views_path() . 'partial-result-messages.php';
	return ob_get_clean();
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
			'pledge-contributors' => FILTER_UNSAFE_RAW,
			'pledge-agreement'    => FILTER_VALIDATE_BOOLEAN,
		)
	);

	$result = filter_input_array( INPUT_POST, $input_filters );
	if ( ! $result ) {
		$result               = array_fill_keys( array_keys( $input_filters ), '' );
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
