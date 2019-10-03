<?php
/**
 *
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeForm;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\Pledge;
use WordPressDotOrg\FiveForTheFuture\PledgeMeta;
use WP_Error;

defined( 'WPINC' ) || die();

add_shortcode( '5ftf_pledge_form_new', __NAMESPACE__ . '\render_form_new' );
add_shortcode( '5ftf_pledge_form_manage', __NAMESPACE__ . '\render_form_manage' );

/**
 * Render the form(s) for creating new pledges.
 *
 * @return false|string
 */
function render_form_new() {
	$action   = filter_input( INPUT_POST, 'action' );
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
	require FiveForTheFuture\PATH . 'views/form-pledge-new.php';

	return ob_get_clean();
}

/**
 *
 *
 * @return string|WP_Error String "success" if the form processed correctly. Otherwise WP_Error.
 */
function process_form_new() {
	$submission = filter_input_array( INPUT_POST, get_input_filters() );

	$has_required = PledgeMeta\has_required_pledge_meta( $submission );

	if ( is_wp_error( $has_required ) ) {
		return $has_required;
	}

	$domain = PledgeMeta\get_normalized_domain_from_url( $submission['org-url'] );

	if ( has_existing_pledge( $domain ) ) {
		return new WP_Error(
			'existing_pledge',
			__( 'A pledge already exists for this domain.', 'wporg' )
		);
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

	//PledgeMeta\save_pledge_meta( $created, $submission );

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

	if ( 'Update Pledge' === $action ) {
		$processed = process_form_manage();

		if ( is_wp_error( $processed ) ) {
			$messages = array_merge( $messages, $processed->get_error_messages() );
		} elseif ( 'success' === $processed ) {
			$updated = true;
		}
	}

	ob_start();
	require FiveForTheFuture\PATH . 'views/form-pledge-manage.php';

	return ob_get_clean();
}

/**
 *
 *
 * @return string|WP_Error String "success" if the form processed correctly. Otherwise WP_Error.
 */
function process_form_manage() {
	$submission = filter_input_array( INPUT_POST, get_input_filters() );

	$has_required = PledgeMeta\has_required_pledge_meta( $submission );

	if ( is_wp_error( $has_required ) ) {
		return $has_required;
	}

	$domain = PledgeMeta\get_normalized_domain_from_url( $submission['org-url'] );

	if ( has_existing_pledge( $domain ) ) {
		return new WP_Error(
			'existing_pledge',
			__( 'A pledge already exists for this domain.', 'wporg' )
		);
	}
}

/**
 *
 *
 * @return array
 */
function get_input_filters() {
	return array_merge(
		// Inputs that correspond to meta values.
		wp_list_pluck( PledgeMeta\get_pledge_meta_config( 'user_input' ), 'php_filter' ),
		// Inputs with no corresponding meta value.
		array(
			'contributor-wporg-usernames' => [
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_REQUIRE_ARRAY,
			],
			'pledge-agreement'            => FILTER_VALIDATE_BOOLEAN,
		)
	);
}

/**
 *
 *
 * @param string $domain
 * @param int    $current_pledge_id
 *
 * @return bool
 */
function has_existing_pledge( $domain, int $current_pledge_id = 0 ) {
	$args = array(
		'post_type'   => Pledge\CPT_ID,
		'post_status' => array( 'pending', 'publish' ),
		'meta_query'  => array(
			'key'   => PledgeMeta\META_PREFIX . 'org-domain',
			'value' => $domain,
		),
	);

	if ( $current_pledge_id ) {
		$args['exclude'] = array( $current_pledge_id );
	}

	$matching_pledge = get_posts( $args );

	return ! empty( $matching_pledge );
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
