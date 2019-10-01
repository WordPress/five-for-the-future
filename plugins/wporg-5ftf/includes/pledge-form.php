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
 * @param $attributes
 * @param $content
 *
 * @return false|string
 */
function render_shortcode( $attributes, $content ) {
	$action   = filter_input( INPUT_POST, 'action' );
	$messages = [];
	$complete = false;
	$html     = '';

	if ( 'Submit' === $action ) {
		$processed = process_form( $_POST );

		if ( is_wp_error( $processed ) ) {
			$messages = array_merge( $messages, $processed->get_error_messages() );
		} elseif ( 'success' === $processed ) {
			$complete = true;
		}
	}

	if ( $complete ) {
		$html = wpautop( __( 'Thank you for your submission.', 'wporg' ) );
	} else {
		ob_start();
		require FiveForTheFuture\PATH . 'views/pledge-form.php';
		$html = ob_get_clean();
	}

	return $html;
}

/**
 *
 *
 * @param array $form_values
 *
 * @return string|WP_Error String "success" if the form processed correctly. Otherwise WP_Error.
 */
function process_form( array $form_values ) {
	$required_fields = PledgeMeta\has_required_pledge_meta( $form_values );

	if ( is_wp_error( $required_fields ) ) {
		return $required_fields;
	}

	$name = sanitize_meta(
		PledgeMeta\META_PREFIX . 'company-name',
		$form_values['company-name'],
		'post',
		Pledge\CPT_ID
	);

	$created = create_new_pledge( $name );

	if ( is_wp_error( $created ) ) {
		return $created;
	}

	PledgeMeta\save_pledge_meta( $created, $form_values );
	// save teams contirbuted to as terms

	return 'success';
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
