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

add_action( 'init', __NAMESPACE__ . '\register_pledge_meta' );
add_action( 'admin_init', __NAMESPACE__ . '\add_meta_boxes' );
add_action( 'save_post', __NAMESPACE__ . '\save_pledge', 10, 2 );

/**
 * Define pledge meta fields and their properties.
 *
 * @return array
 */
function get_pledge_meta_config() {
	return array(
		'org-description' => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_SANITIZE_STRING
		),
		'org-domain'      => array( // This value is derived programmatically from `org-url`.
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => false,
			'php_filter'        => FILTER_SANITIZE_STRING,
		),
		'org-name'        => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_SANITIZE_STRING
		),
		'org-url'         => array(
			'single'            => true,
			'sanitize_callback' => 'esc_url_raw',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_VALIDATE_URL,
		),
		'pledge-email'    => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_email',
			'show_in_rest'      => false,
			'php_filter'        => FILTER_VALIDATE_EMAIL
		),
	);
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
	$editable = current_user_can( 'edit_pledge', $pledge->ID );

	switch ( $box['id'] ) {
		case 'pledge-email':
			$email     = get_post_meta( $pledge->ID, META_PREFIX . 'pledge-email', true );
			$confirmed = get_post_meta( $pledge->ID, META_PREFIX . 'pledge-email-confirmed', true );
			break;
		case 'org-info':
			$data = array();

			foreach ( get_pledge_meta_config() as $key => $config ) {
				$data[ $key ] = get_post_meta( $pledge->ID, META_PREFIX . $key, $config['single'] );
			}
			break;
		case 'pledge-contributors':

			break;
	}

	require dirname( __DIR__ ) . '/views/metabox-' . sanitize_file_name( $box['id'] ) . '.php';
}

/**
 * Check that an array contains values for all required keys.
 *
 * @return bool|WP_Error True if all required values are present. Otherwise WP_Error.
 */
function has_required_pledge_meta( array $submission ) {
	$error = new WP_Error();

	foreach ( $submission as $key => $value ) {
		if ( is_null( $value ) ) {
			$error->add(
				'required_field',
				sprintf(
					__( 'The <code>%s</code> field does not have a value.', 'wporg' ),
					sanitize_key( $key )
				)
			);
		} elseif ( false === $value ) {
			$error->add(
				'required_field',
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
 * @param int     $pledge_id
 * @param WP_Post $pledge
 */
function save_pledge( $pledge_id, $pledge ) {
	$action          = filter_input( INPUT_GET, 'action' );
	$ignored_actions = array( 'trash', 'untrash', 'restore' );

	if ( $action && in_array( $action, $ignored_actions, true ) ) {
		return;
	}

	if ( ! $pledge instanceof WP_Post || $pledge->post_type !== Pledge\CPT_ID ) {
		return;
	}

	if ( ! current_user_can( 'edit_pledge', $pledge_id ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $pledge->post_status === 'auto-draft' ) {
		return;
	}

	$submitted_meta = filter_input_array( INPUT_POST, wp_list_pluck( get_pledge_meta_config(), 'php_filter' ) );

	if ( is_wp_error( has_required_pledge_meta( $submitted_meta ) ) ) {
		return;
	}

	save_pledge_meta( $pledge_id, $submitted_meta );
}

/**
 * Save the pledge's meta fields
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

// maybe set the wporg username as the company author, so they can edit it themselves to keep it updated,
// then make the user a contributor if they don't already have a role on the site
// setup cron to automatically email once per quarter
// "here's all the info we have: x, y, z"
// is that still accurate? if not, click here to update it
// if want to be removed from public listing, emailing support@wordcamp.org
// don't let them edit the "featured" taxonomy, only admins
