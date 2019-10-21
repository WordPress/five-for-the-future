<?php
/**
 * Render and process the pledge forms.
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeForm;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\Pledge;
use WordPressDotOrg\FiveForTheFuture\PledgeMeta;
use WP_Error, WP_Post, WP_User;

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
	$action   = filter_input( INPUT_POST, 'action' );
	$data     = get_form_submission();
	$messages = [];
	$complete = false;

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

	$contributors = parse_contributors( $submission['org-pledge-contributors'] );

	if ( is_wp_error( $contributors ) ) {
		return $contributors;
	}

	$name = sanitize_meta(
		PledgeMeta\META_PREFIX . 'org-name',
		$submission['org-name'],
		'post',
		Pledge\CPT_ID
	);

	$created = create_new_pledge( $name );

	if ( is_wp_error( $created ) ) {
		return $created;
	}

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
			'existing_pledge',
			__( 'A pledge already exists for this domain.', 'wporg' )
		);
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
			'org-pledge-contributors' => FILTER_SANITIZE_STRING,
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
 * TODO Move this to the contributor cpt include file.
 *
 * @param int $pledge_id
 *
 * @return array
 */
function get_pledge_contributors( $pledge_id = 0 ) {
	$contributors = array();

	// Get POST'd submission, if it exists.
	$submission = filter_input( INPUT_POST, 'org-pledge-contributors', FILTER_SANITIZE_STRING );

	// Get existing pledge, if it exists.
	$pledge = get_post( $pledge_id );

	if ( ! empty( $submission ) ) {
		$contributors = array_map( 'sanitize_user', explode( ',', $submission ) );
	} elseif ( $pledge instanceof WP_Post ) {
		// TODO the Contributor post type is being introduced in a separate PR. These details may change.

		$contributor_posts = get_posts( array(
			'post_type'   => '',
			'post_status' => array( 'pending', 'publish' ),
			'post_parent' => $pledge_id,
			'numberposts' => -1,
		) );

		$contributors = wp_list_pluck( $contributor_posts, 'post_title' );
	}

	return $contributors;
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
 *
 *
 * @param string $name The name of the company to use as the post title.
 *
 * @return int|WP_Error Post ID on success. Otherwise WP_Error.
 */
function create_new_pledge( $name ) {
	$args = [
		'post_type'   => Pledge\CPT_ID,
		'post_title'  => $name,
		'post_status' => 'draft',
	];

	return wp_insert_post( $args, true );
}
