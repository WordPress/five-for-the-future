<?php
/**
 * This file handles the operations related to registering and handling pledge meta values for the CPT.
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeMeta;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\Pledge;
use WP_Post, WP_Error;

defined( 'WPINC' ) || die();

const META_PREFIX = FiveForTheFuture\PREFIX . '-';

add_action( 'init', __NAMESPACE__ . '\register_pledge_meta' );
add_action( 'admin_init', __NAMESPACE__ . '\add_meta_boxes' );
add_action( 'save_post', __NAMESPACE__ . '\save_pledge', 10, 2 );

/**
 * Define pledge meta fields and their properties.
 *
 * @return array
 */
function get_pledge_meta_config() {
	return [
		'company-name'            => [
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'required'          => true,
		],
		'company-url'             => [
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'required'          => true,
		],
		'company-email'           => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_email',
			'required'          => true,
		],
		'company-phone'           => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field',
			'required'          => false,
		],
		'company-total-employees' => [
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'required'          => true,
		],
		'contact-name'            => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field',
			'required'          => true,
		],
		'contact-wporg-username'  => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_user',
			'required'          => true,
		],
		'pledge-hours'            => [
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'required'          => true,
		],
		'pledge-agreement'        => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'wp_validate_boolean',
			'required'          => true,
		],
	];
}

/**
 * Register post meta keys for the custom post type.
 */
function register_pledge_meta() {
	$meta = get_pledge_meta_config();

	foreach ( $meta as $key => $args ) {
		$meta_key = META_PREFIX . $key;

		register_post_meta( Pledge\CPT_ID, $meta_key, $args );
	}
}

/**
 * Adds meta boxes for the custom post type
 */
function add_meta_boxes() {
	add_meta_box(
		'company-information',
		__( 'Company Information', 'wordpressorg' ),
		__NAMESPACE__ . '\render_meta_boxes',
		Pledge\CPT_ID,
		'normal',
		'default'
	);
}

/**
 * Builds the markup for all meta boxes
 *
 * @param WP_Post $pledge
 * @param array   $box
 */
function render_meta_boxes( $pledge, $box ) {
	switch ( $box['id'] ) {
		case 'company-information':
			require dirname( __DIR__ ) . '/views/metabox-' . sanitize_file_name( $box['id'] ) . '.php';
			break;
	}
}

/**
 * Check that an array contains values for all required keys.
 *
 * @return bool|WP_Error True if all required values are present.
 */
function has_required_pledge_meta( array $values ) {
	$config   = get_pledge_meta_config();
	$plucked  = wp_list_pluck( get_pledge_meta_config(), 'required' );
	$required = array_combine( array_keys( $config ), $plucked );

	$required_keys = array_keys( $required, true, true );
	$error         = new WP_Error();

	foreach ( $required_keys as $key ) {
		if ( ! isset( $values[ $key ] ) || empty( $values[ $key ] ) ) {
			$error->add(
				'required_field',
				__( 'Please fill all required fields.', 'wporg' )
			);

			break;
		}
	}

	if ( ! empty( $error->get_error_messages() ) ) {
		return $error;
	}

	return true;
}

/**
 * Save the pledge data
 *
 * @param int     $pledge_id
 * @param WP_Post $pledge
 */
function save_pledge( $pledge_id, $pledge ) {
	$ignored_actions = array( 'trash', 'untrash', 'restore' );

	if ( isset( $_GET['action'] ) && in_array( $_GET['action'], $ignored_actions, true ) ) {
		return;
	}

	if ( ! $pledge || $pledge->post_type !== Pledge\CPT_ID || ! current_user_can( 'edit_pledge', $pledge_id ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $pledge->post_status === 'auto-draft' ) {
		return;
	}

	if ( is_wp_error( has_required_pledge_meta( $_POST ) ) ) {
		return;
	}

	save_pledge_meta( $pledge_id, $_POST );
}

/**
 * Save the pledge's meta fields
 *
 * @param int   $pledge_id
 * @param array $new_values
 */
function save_pledge_meta( $pledge_id, $new_values ) {
	$keys = array_keys( get_pledge_meta_config() );

	foreach ( $keys as $key ) {
		$meta_key = META_PREFIX . $key;

		if ( isset( $new_values[ $key ] ) ) {
			// Since the sanitize callback is called during this function, it could still end up
			// saving an empty value to the database.
			update_post_meta( $pledge_id, $meta_key, $new_values[ $key ] );
		} else {
			delete_post_meta( $pledge_id, $meta_key );
		}
	}

	// maybe set the wporg username as the company author, so they can edit it themselves to keep it updated,
	// then make the user a contributor if they don't already have a role on the site
	// setup cron to automatically email once per quarter
	// "here's all the info we have: x, y, z"
	// is that still accurate? if not, click here to update it
	// if want to be removed from public listing, emailing support@wordcamp.org
	// don't let them edit the "featured" taxonomy, only admins
}
