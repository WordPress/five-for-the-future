<?php
/**
 * This file handles the operations related to registering and handling pledge meta values for the CPT.
 */

namespace WordPressDotOrg\FiveForTheFuture\PledgeMeta;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\Pledge;
use WordPressDotOrg\FiveForTheFuture\PledgeForm;
use WordPressDotOrg\FiveForTheFuture\Contributor;
use WP_Post, WP_Error;

defined( 'WPINC' ) || die();

const META_PREFIX = FiveForTheFuture\PREFIX . '_';

add_action( 'init',                   __NAMESPACE__ . '\register_pledge_meta' );
add_action( 'admin_init',             __NAMESPACE__ . '\add_meta_boxes' );
add_action( 'save_post',              __NAMESPACE__ . '\save_pledge', 10, 2 );
add_action( 'admin_enqueue_scripts',  __NAMESPACE__ . '\enqueue_assets' );
add_action( 'transition_post_status', __NAMESPACE__ . '\update_confirmed_contributor_count', 10, 3 );

// Both hooks must be used because `updated` doesn't fire if the post meta didn't previously exist.
add_action( 'updated_postmeta', __NAMESPACE__ . '\update_generated_meta', 10, 4 );
add_action( 'added_post_meta',  __NAMESPACE__ . '\update_generated_meta', 10, 4 );

/**
 * Define pledge meta fields and their properties.
 *
 * @return array
 */
function get_pledge_meta_config( $context = '' ) {
	$user_input = array(
		'org-description'  => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_SANITIZE_STRING,
		),
		'org-name'         => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_SANITIZE_STRING,
		),
		'org-url'          => array(
			'single'            => true,
			'sanitize_callback' => 'esc_url_raw',
			'show_in_rest'      => true,
			'php_filter'        => FILTER_VALIDATE_URL,
		),
		'org-pledge-email' => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_email',
			'show_in_rest'      => false,
			'php_filter'        => FILTER_VALIDATE_EMAIL,
		),
	);

	$generated = array(
		'org-domain'                    => array(
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => false,
		),
		'pledge-email-confirmed'        => array(
			'single'            => true,
			'sanitize_callback' => 'wp_validate_boolean',
			'show_in_rest'      => false,
		),
		'pledge-confirmed-contributors' => array(
			'single'            => true,
			'sanitize_callback' => 'absint',
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

	$data = array();
	foreach ( get_pledge_meta_config() as $key => $config ) {
		$data[ $key ] = get_post_meta( $pledge->ID, META_PREFIX . $key, $config['single'] );
	}

	$contributors = Contributor\get_pledge_contributors( $pledge->ID, 'all' );

	echo '<div class="pledge-form">';

	switch ( $box['id'] ) {
		case 'pledge-email':
			require FiveForTheFuture\get_views_path() . 'inputs-pledge-org-email.php';
			break;

		case 'org-info':
			require FiveForTheFuture\get_views_path() . 'inputs-pledge-org-info.php';
			break;

		case 'pledge-contributors':
			require FiveForTheFuture\get_views_path() . 'manage-contributors.php';
			break;
	}

	echo '</div>';
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
	$get_action      = filter_input( INPUT_GET, 'action' );
	$post_action     = filter_input( INPUT_POST, 'action' );
	$ignored_actions = array( 'trash', 'untrash', 'restore' );

	/*
	 * This is only intended to run when the front end form and wp-admin forms are submitted, not when posts are
	 * programmatically updated.
	 */
	if ( 'Submit Pledge' !== $post_action && 'editpost' !== $post_action ) {
		return;
	}

	if ( $get_action && in_array( $get_action, $ignored_actions, true ) ) {
		return;
	}

	if ( ! $pledge instanceof WP_Post || Pledge\CPT_ID !== $pledge->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_pledge', $pledge_id ) ) {
		// todo re-enable once setup cap mapping or whatever.
		//return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'auto-draft' === $pledge->post_status ) {
		return;
	}

	$submitted_meta = PledgeForm\get_form_submission();

	if ( is_wp_error( has_required_pledge_meta( $submitted_meta ) ) ) {
		return;
	}

	save_pledge_meta( $pledge_id, $submitted_meta );

	if ( filter_input( INPUT_POST, 'resend-pledge-confirmation' ) ) {
		Pledge\send_pledge_confirmation_email(
			filter_input( INPUT_GET, 'resend-pledge-id', FILTER_VALIDATE_INT ),
			get_page_by_path( 'for-organizations' )->ID
		);
	}

	if ( filter_input( INPUT_POST, 'resend-contributor-confirmation' ) ) {
		PledgeForm\send_contributor_confirmation_emails(
			$pledge_id,
			filter_input( INPUT_GET, 'resend-contributor-id', FILTER_VALIDATE_INT )
		);
	}
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
	$post_type = get_post_type( $object_id );

	if ( Pledge\CPT_ID !== $post_type ) {
		return;
	}

	switch ( $meta_key ) {
		case META_PREFIX . 'org-name':
			if ( 'updated_postmeta' === current_action() ) {
				wp_update_post( array(
					'post_title' => $_meta_value,
				) );
			}
			break;

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
 * Update the cached count of confirmed contributors for a pledge when a contributor post changes statuses.
 *
 * Note that contributor posts should always be trashed instead of deleted completely when a contributor is
 * removed from a pledge.
 *
 * @param string  $new_status
 * @param string  $old_status
 * @param WP_Post $post
 *
 * @return void
 */
function update_confirmed_contributor_count( $new_status, $old_status, WP_Post $post ) {
	if ( Contributor\CPT_ID !== get_post_type( $post ) ) {
		return;
	}

	if ( $new_status === $old_status ) {
		return;
	}

	$pledge = get_post( $post->post_parent );

	if ( $pledge instanceof WP_Post ) {
		$confirmed_contributors = Contributor\get_pledge_contributors( $pledge->ID, 'publish' );

		update_post_meta( $pledge->ID, META_PREFIX . 'pledge-confirmed-contributors', count( $confirmed_contributors ) );
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
 * Get the metadata for a given pledge, or a default set if no pledge is provided.
 *
 * @param int    $pledge_id
 * @param string $context
 * @return array Pledge data
 */
function get_pledge_meta( $pledge_id = 0, $context = '' ) {
	// Get existing pledge, if it exists.
	$pledge = get_post( $pledge_id );

	$keys = get_pledge_meta_config( $context );
	$meta = array();

	// Get POST'd submission, if it exists.
	$submission = PledgeForm\get_form_submission();

	foreach ( $keys as $key => $config ) {
		if ( isset( $submission[ $key ] ) ) {
			$meta[ $key ] = $submission[ $key ];
		} elseif ( $pledge instanceof WP_Post ) {
			$meta_key     = META_PREFIX . $key;
			$meta[ $key ] = get_post_meta( $pledge->ID, $meta_key, true );
		} else {
			$meta[ $key ] = $config['default'] ?: '';
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

/**
 * Enqueue CSS file for admin page.
 *
 * @return void
 */
function enqueue_assets() {
	$ver = filemtime( FiveForTheFuture\PATH . '/assets/css/admin.css' );
	wp_register_style( '5ftf-admin', plugins_url( 'assets/css/admin.css', __DIR__ ), [], $ver );

	$current_page = get_current_screen();
	if ( Pledge\CPT_ID === $current_page->id ) {
		wp_enqueue_style( '5ftf-admin' );
	}
}
