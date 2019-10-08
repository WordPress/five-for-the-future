<?php
/**
 * This file handles the operations related to registering and handling pledge meta values for the CPT.
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeMeta;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\Pledge;
use WP_Post, WP_Error;

defined( 'WPINC' ) || die();

const META_PREFIX = FiveForTheFuture\PREFIX . '_';

add_action( 'init',                               __NAMESPACE__ . '\register_pledge_meta' );
add_action( 'admin_init',                         __NAMESPACE__ . '\add_meta_boxes' );
add_action( 'save_post',                          __NAMESPACE__ . '\save_pledge',           10, 2 );
add_action( 'updated_' . Pledge\CPT_ID . '_meta', __NAMESPACE__ . '\update_generated_meta', 10, 4 );

/**
 * Define pledge meta fields and their properties.
 *
 * @return array
 */
function get_pledge_meta_config( $context = '' ) {
	$user_input = array(
		'org-description' => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_SANITIZE_STRING,
		),
		'org-name'        => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_SANITIZE_STRING,
		),
		'org-url'         => array(
			'single'            => true,
			'sanitize_callback' => 'esc_url_raw',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_VALIDATE_URL,
		),
		'org-pledge-email'    => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_email',
			'show_in_rest'      => false,
			'php_filter'        => FILTER_VALIDATE_EMAIL,
		),
	);

	$generated = array(
		'org-domain'             => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => false,
		),
		'pledge-email-confirmed' => array(
			'single'            => true,
			'sanitize_callback' => 'wp_validate_boolean',
			'show_in_rest'      => false,
		),
	);

	switch ( $context ) {
		case 'user_input':
			$return = $user_input;
			break;
		case 'generated':
			$return = $generated;
			break;
		default:
			$return = array_merge( $user_input, $generated );
			break;
	}

	return $return;
}

/**
 * Register post meta keys for the custom post type.
 *
 * @return void
 */
function register_pledge_meta() {
	$meta = get_pledge_meta_config();

	foreach ( $meta as $key => $args ) {
		$meta_key = META_PREFIX . $key;

		register_post_meta( Pledge\CPT_ID, $meta_key, $args );
	}
}

/**
 * Adds meta boxes for the custom post type.
 *
 * @return void
 */
function add_meta_boxes() {
	add_meta_box(
		'pledge-email',
		__( 'Pledge Email', 'wordpressorg' ),
		__NAMESPACE__ . '\render_meta_boxes',
		Pledge\CPT_ID,
		'normal',
		'high'
	);

	add_meta_box(
		'org-info',
		__( 'Organization Information', 'wordpressorg' ),
		__NAMESPACE__ . '\render_meta_boxes',
		Pledge\CPT_ID,
		'normal',
		'high'
	);

	add_meta_box(
		'pledge-contributors',
		__( 'Contributors', 'wordpressorg' ),
		__NAMESPACE__ . '\render_meta_boxes',
		Pledge\CPT_ID,
		'normal',
		'high'
	);
}

/**
 * Builds the markup for all meta boxes
 *
 * @param WP_Post $pledge
 * @param array   $box
 */
function render_meta_boxes( $pledge, $box ) {
	$readonly = ! current_user_can( 'edit_page', $pledge->ID );
	$data     = array();

	foreach ( get_pledge_meta_config() as $key => $config ) {
		$data[ $key ] = get_post_meta( $pledge->ID, META_PREFIX . $key, $config['single'] );
	}

	switch ( $box['id'] ) {
		case 'pledge-email':
			require FiveForTheFuture\get_views_path() . 'inputs-pledge-org-email.php';
			break;
		case 'org-info':
			require FiveForTheFuture\get_views_path() . 'inputs-pledge-org-info.php';
			break;

		case 'pledge-contributors':
			require FiveForTheFuture\get_views_path() . 'inputs-pledge-contributors.php';
			break;
	}
}

/**
 * Check that an array contains values for all required keys.
 *
 * @return bool|WP_Error True if all required values are present. Otherwise WP_Error.
 */
function has_required_pledge_meta( array $submission ) {
	$error = new WP_Error();

	$required = array_keys( get_pledge_meta_config( 'user_input' ) );

	foreach ( $required as $key ) {
		if ( ! isset( $submission[ $key ] ) || is_null( $submission[ $key ] ) ) {
			$error->add(
				'required_field_empty',
				sprintf(
					__( 'The <code>%s</code> field does not have a value.', 'wporg' ),
					sanitize_key( $key )
				)
			);
		} elseif ( false === $submission[ $key ] ) {
			$error->add(
				'required_field_invalid',
				sprintf(
					__( 'The <code>%s</code> field has an invalid value.', 'wporg' ),
					sanitize_key( $key )
				)
			);
		}
	}

	if ( ! empty( $error->get_error_messages() ) ) {
		return $error;
	}

	return true;
}

/**
 * Save the pledge data.
 *
 * This only fires when the pledge post itself is created or updated.
 *
 * @param int     $pledge_id
 * @param WP_Post $pledge
 */
function save_pledge( $pledge_id, $pledge ) {
	$action          = filter_input( INPUT_GET, 'action' );
	$ignored_actions = array( 'trash', 'untrash', 'restore' );

	if ( $action && in_array( $action, $ignored_actions, true ) ) {
		return;
	}

	if ( ! $pledge instanceof WP_Post || Pledge\CPT_ID !== $pledge->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_pledge', $pledge_id ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'auto-draft' === $pledge->post_status ) {
		return;
	}

	$definitions    = wp_list_pluck( get_pledge_meta_config( 'user_input' ), 'php_filter' );
	$submitted_meta = filter_input_array( INPUT_POST, $definitions );

	if ( is_wp_error( has_required_pledge_meta( $submitted_meta ) ) ) {
		return;
	}

	save_pledge_meta( $pledge_id, $submitted_meta );
}

/**
 * Save the pledge's meta fields.
 *
 * @param int   $pledge_id
 * @param array $new_values
 *
 * @return void
 */
function save_pledge_meta( $pledge_id, $new_values ) {
	$config = get_pledge_meta_config();

	foreach ( $new_values as $key => $value ) {
		if ( array_key_exists( $key, $config ) ) {
			$meta_key = META_PREFIX . $key;
			// Since the sanitize callback is called during this function, it could still end up
			// saving an empty value to the database.
			update_post_meta( $pledge_id, $meta_key, $value );
		}
	}
}

/**
 * Updated some generated meta values based on changes in user input meta values.
 *
 * This is hooked to the `updated_{$meta_type}_meta` action, which only fires if a submitted post meta value
 * is different from the previous value. Thus here we assume the values of specific meta keys are changed
 * when they come through this function.
 *
 * @param int    $meta_id
 * @param int    $object_id
 * @param string $meta_key
 * @param mixed  $_meta_value
 *
 * @return void
 */
function update_generated_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
	switch ( $meta_key ) {
		case META_PREFIX . 'org-url':
			$domain = get_normalized_domain_from_url( $_meta_value );
			update_post_meta( $object_id, META_PREFIX . 'org-domain', $domain );
			break;

		case META_PREFIX . 'pledge-email':
			delete_post_meta( $object_id, META_PREFIX . 'pledge-email-confirmed' );
			break;
	}
}

/**
 * Get the input filters for submitted content.
 *
 * @return array
 */
function get_input_filters() {
	return array_merge(
		// Inputs that correspond to meta values.
		wp_list_pluck( get_pledge_meta_config( 'user_input' ), 'php_filter' ),
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
 * Get the metadata for a given pledge, or a default set if no pledge is provided.
 *
 * @param int    $pledge_id
 * @param string $context
 * @return array Pledge data
 */
function get_pledge_meta( $pledge_id = 0, $context = '' ) {
	$pledge = get_post( $pledge_id );

	$keys = get_pledge_meta_config( $context );
	$meta = array();

	foreach ( $keys as $key => $config ) {
		if ( ! $pledge instanceof WP_Post ) {
			$meta[ $key ] = $config['default'] ?: '';
		} else {
			$meta_key = META_PREFIX . $key;
			$meta[ $key ] = get_post_meta( $pledge->ID, $meta_key, true );
		}
	}

	return $meta;
}

/**
 * Isolate the domain from a given URL and remove the `www.` if necessary.
 *
 * @param string $url
 *
 * @return string
 */
function get_normalized_domain_from_url( $url ) {
	$domain = wp_parse_url( $url, PHP_URL_HOST );
	$domain = preg_replace( '#^www\.#', '', $domain );

	return $domain;
}
