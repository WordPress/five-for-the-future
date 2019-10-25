<?php
/**
 * Render and process the pledge forms.
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeForm;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Pledge, PledgeMeta, Contributor };
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
	$action        = filter_input( INPUT_POST, 'action' );
	$data          = get_form_submission();
	$messages      = [];
	$complete      = false;
	$directory_url = get_permalink( get_page_by_path( 'pledges' ) );

	if ( 'Submit Pledge' === $action ) {
		$processed = process_form_new();

		if ( is_wp_error( $processed ) ) {
			$messages = array_merge( $messages, $processed->get_error_messages() );
		} elseif ( 'success' === $processed ) {
			$complete = true;
		}
	}

	ob_start();
	$readonly = false;
	require FiveForTheFuture\PATH . 'views/form-pledge-new.php';

	return ob_get_clean();
}

/**
 * Process a submission from the New Pledge form.
 *
 * @return string|WP_Error String "success" if the form processed correctly. Otherwise WP_Error.
 */
function process_form_new() {
	$submission = get_form_submission();
	$has_error = validate_submission( $submission );
	if ( $has_error ) {
		return $has_error;
	}

	$contributors = parse_contributors( $submission['pledge-contributors'] );
	if ( is_wp_error( $contributors ) ) {
		return $contributors;
	}

	// Process image.
	if ( ! function_exists('media_handle_upload') ) {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
	}

	$logo = isset( $_FILES['org-logo'] ) ? $_FILES['org-logo'] : false;
	if ( $logo ) {
		$logo_id = \media_handle_sideload( $logo, 0 );
		if ( is_wp_error( $logo_id ) ) {
			return $logo_id;
		}
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

	foreach ( $contributors as $wporg_username ) {
		Contributor\create_new_contributor( $wporg_username, $new_pledge_id );
	}

	set_post_thumbnail( $new_pledge_id, $logo_id );

	return 'success';
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
	$has_error = validate_submission( $submission );
	if ( $has_error ) {
		return $has_error;
	}
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

	$contributors = explode( ',', $contributors );

	foreach ( $contributors as $wporg_username ) {
		$sanitized_username = sanitize_user( $wporg_username );
		$user               = get_user_by( 'login', $sanitized_username );

		if ( $user instanceof WP_User ) {
			$sanitized_contributors[] = $sanitized_username;
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
 * @return false|WP_Error
 */
function validate_submission( $submission ) {
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
