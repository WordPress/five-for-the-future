<?php

/**
 * This file handles the operations related to registering and handling company meta values for the CPT.
 */

namespace WordPressDotOrg\FiveForTheFuture\CompanyMeta;
use WordPressDotOrg\FiveForTheFuture\Company;
use WP_Post, WP_Error;

defined( 'WPINC' ) || die();

const META_PREFIX = '5ftf-';

/**
 *
 */
function register() {
	register_company_meta();
}

add_action( 'init', __NAMESPACE__ . '\register' );

/**
 * Define company meta fields and their properties.
 *
 * @return array
 */
function get_company_meta_config() {
	return [
		'company-name' => [
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'required'          => true,
		],
		'company-url' => [
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'required'          => true,
		],
		'company-email' => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_email',
			'required'          => true,
		],
		'company-phone' => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field',
			'required'          => false,
		],
		'company-total-employees' => [
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'required'          => true,
		],
		// todo add # sponsored employees here and also to form, etc
		'contact-name' => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field',
			'required'          => true,
		],
		'contact-wporg-username' => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_user',
			'required'          => true,
		],
		'pledge-hours' => [
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'required'          => true,
		],
		'pledge-agreement' => [
			'show_in_rest'      => false,
			'sanitize_callback' => 'wp_validate_boolean',
			'required'          => true,
		],
	];
}

/**
 * Register post meta keys for the Company post type.
 */
function register_company_meta() {
	$meta = get_company_meta_config();

	foreach ( $meta as $key => $args ) {
		$meta_key = META_PREFIX . $key;

		register_post_meta( Company\CPT_SLUG, $meta_key, $args );
	}
}

/**
 * Adds meta boxes for the custom post type
 */
function add_meta_boxes() {
	add_meta_box(
		'company-information',
		__( 'Company Information', 'wordpressorg' ),
		__NAMESPACE__ . '\markup_meta_boxes',
		Company\CPT_SLUG,
		'normal',
		'default'
	);
}

add_action( 'admin_init', __NAMESPACE__ . '\add_meta_boxes' );

/**
 * Builds the markup for all meta boxes
 *
 * @param WP_Post $company
 * @param array   $box
 */
function markup_meta_boxes( $company, $box ) {
	/** @var $view string */

	switch ( $box['id'] ) {
		case 'company-information':
			$wporg_user = get_user_by( 'login', $company->_5ftf_wporg_username );
			$avatar_url = $wporg_user ? get_avatar_url( $wporg_user->ID ) : false;
			break;
	}

	require_once( dirname( __DIR__ ) . '/views/metabox-' . sanitize_file_name( $box['id'] ) . '.php' );
}

/**
 * Check that an array contains values for all required keys.
 *
 * @return bool|WP_Error True if all required values are present.
 */
function has_required_company_meta( array $values ) {
	$config   = get_company_meta_config();
	$plucked  = wp_list_pluck( get_company_meta_config(), 'required' );
	$required = array_combine( array_keys( $config ), $plucked );

	$required_keys = array_keys( $required, true );
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
 * Save the company data
 *
 * @param int     $company_id
 * @param WP_Post $company
 */
function save_company( $company_id, $company ) {
	$ignored_actions = array( 'trash', 'untrash', 'restore' );

	if ( isset( $_GET['action'] ) && in_array( $_GET['action'], $ignored_actions ) ) {
		return;
	}

	if ( ! $company || $company->post_type != Company\CPT_SLUG || ! current_user_can( 'edit_company', $company_id ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $company->post_status == 'auto-draft' ) {
		return;
	}

	if ( is_wp_error( has_required_company_meta( $_POST ) ) ) {
		return;
	}

	save_company_meta( $company_id, $_POST );
}


add_action( 'save_post', __NAMESPACE__ . '\save_company', 10, 2 );

/**
 * Save the company's meta fields
 *
 * @param int   $company_id
 * @param array $new_values
 */
function save_company_meta( $company_id, $new_values ) {
	$keys = array_keys( get_company_meta_config() );

	foreach ( $keys as $key ) {
		$meta_key = META_PREFIX . $key;

		if ( isset( $new_values[ $key ] ) ) {
			// Since the sanitize callback is called during this function, it could still end up
			// saving an empty value to the database.
			update_post_meta( $company_id, $meta_key, $new_values[ $key ] );
		} else {
			delete_post_meta( $company_id, $meta_key );
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
