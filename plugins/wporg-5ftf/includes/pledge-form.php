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

add_shortcode( 'five_for_the_future_pledge_form', __NAMESPACE__ . '\render_shortcode' );

/**
 *
 *
 * @return false|string
 */
function render_shortcode() {
	$action   = filter_input( INPUT_POST, 'action' );
	$messages = [];
	$complete = false;

	if ( 'Submit Pledge' === $action ) {
		$processed = process_form();

		if ( is_wp_error( $processed ) ) {
			$messages = array_merge( $messages, $processed->get_error_messages() );
		} elseif ( 'success' === $processed ) {
			$complete = true;
		}
	}

	ob_start();
	require FiveForTheFuture\PATH . 'views/pledge-form.php';

	return ob_get_clean();
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
 * @return string|WP_Error String "success" if the form processed correctly. Otherwise WP_Error.
 */
function process_form() {
	$submission = filter_input_array( INPUT_POST, get_input_filters() );

	$submission['org-domain'] = PledgeMeta\get_normalized_domain_from_url( $submission['org-url'] );

	if ( in_array( null, $submission, true ) || in_array( false, $submission, true ) ) {
		return new WP_Error(
			'invalid_submission',
			__( 'Some fields have missing or invalid information.', 'wporg' )
		);
	}

	$has_existing_pledge = has_existing_pledge( $submission['org-domain'] );

	if ( $has_existing_pledge ) {
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

	PledgeMeta\save_pledge_meta( $created, $submission );

	return 'success';
}

/**
 *
 *
 * @param string $domain
 *
 * @return bool
 */
function has_existing_pledge( $domain ) {
	$matching_pledge = get_posts( array(
		'post_type'   => Pledge\CPT_ID,
		'post_status' => array( 'pending', 'publish' ),
		'meta_query'  => array(
			'key'     => PledgeMeta\META_PREFIX . 'org-domain',
			'value'   => $domain,
			'compare' => 'LIKE',
		),
	) );

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
		'post_status' => 'pending',
		'post_author' => get_current_user_id(), // TODO is this how we want to do this?
	];

	return wp_insert_post( $args, true );
}
