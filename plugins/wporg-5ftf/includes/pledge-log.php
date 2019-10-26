<?php
namespace WordPressDotOrg\FiveForTheFuture\PledgeLog;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Contributor, Pledge, PledgeForm, PledgeMeta };
use WP_Post;

defined( 'WPINC' ) || die();

const LOG_META_KEY = FiveForTheFuture\PREFIX . '_activity-log';

// Log display.
add_action( 'admin_init', __NAMESPACE__ . '\add_log_meta_box' );

// Log capture.
add_action( 'save_post_' . Pledge\CPT_ID, __NAMESPACE__ . '\capture_save_post', 99, 3 );
add_action( 'updated_postmeta', __NAMESPACE__ . '\capture_updated_postmeta', 99, 4 );
add_action( 'added_post_meta', __NAMESPACE__ . '\capture_added_post_meta', 99, 4 );
add_action( 'transition_post_status', __NAMESPACE__ . '\capture_transition_post_status', 99, 3 );

/**
 * Adds a meta box for the log on the custom post type.
 *
 * @return void
 */
function add_log_meta_box() {
	add_meta_box(
		'activity-log',
		__( 'Log', 'wordpressorg' ),
		__NAMESPACE__ . '\render_log_meta_box',
		Pledge\CPT_ID,
		'advanced',
		'low'
	);
}

/**
 * Output the log in the meta box.
 *
 * @param WP_Post $pledge
 *
 * @return void
 */
function render_log_meta_box( $pledge ) {
	$log = get_pledge_log( $pledge->ID );

	require FiveForTheFuture\get_views_path() . 'log.php';
}

/**
 * Defaults for a log entry.
 *
 * @return array
 */
function get_log_entry_template() {
	return array(
		'timestamp' => time(),
		'message'   => '',
		'data'      => array(),
		'user_id'   => 0,
	);
}

/**
 * Get a time-sorted array of log entries for a particular pledge.
 *
 * @param int $pledge_id
 *
 * @return array
 */
function get_pledge_log( $pledge_id ) {
	$log = get_post_meta( $pledge_id, LOG_META_KEY, false );

	if ( ! $log ) {
		return array();
	}

	usort( $log, function( $a, $b ) {
		if ( $a['timestamp'] === $b['timestamp'] ) {
			return 0;
		}

		return ( $a['timestamp'] < $b['timestamp'] ) ? -1 : 1;
	} );

	return $log;
}

/**
 * Add a new log entry for a particular pledge.
 *
 * @param int    $pledge_id
 * @param string $message
 * @param array  $data
 * @param int    $user_id
 *
 * @return void
 */
function add_log_entry( $pledge_id, $message, array $data = array(), $user_id = 0 ) {
	$entry = get_log_entry_template();

	$entry['message'] = $message;
	$entry['data']    = $data;
	$entry['user_id'] = $user_id;

	add_post_meta( $pledge_id, LOG_META_KEY, $entry, false );
}

/**
 * Record logs for events when saving a post.
 *
 * Hooked to "save_post_{$post->post_type}".
 *
 * @param int     $post_ID Post ID.
 * @param WP_Post $post    Unused. Post object.
 * @param bool    $update  Whether this is an existing post being updated or not.
 *
 * @return void
 */
function capture_save_post( $post_ID, $post, $update ) {
	if ( false === $update ) {
		add_log_entry(
			$post_ID,
			sprintf(
				'Pledge created. Status set to <code>%s</code>.',
				esc_html( get_post_status( $post_ID ) )
			),
			PledgeForm\get_form_submission(),
			get_current_user_id()
		);
	}
}

/**
 * Record logs for events when postmeta values change.
 *
 * @param int    $meta_id    Unused. ID of updated metadata entry.
 * @param int    $object_id  Post ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Meta value.
 *
 * @return void
 */
function capture_updated_postmeta( $meta_id, $object_id, $meta_key, $meta_value ) {
	$post_type = get_post_type( $object_id );

	if ( Pledge\CPT_ID !== $post_type ) {
		return;
	}

	$valid_keys       = array_keys( PledgeMeta\get_pledge_meta_config( 'user_input' ) );
	$trimmed_meta_key = str_replace( PledgeMeta\META_PREFIX, '', $meta_key );

	if ( in_array( $trimmed_meta_key, $valid_keys, true ) ) {
		add_log_entry(
			$object_id,
			sprintf(
				'Changed <code>%1$s</code> to <code>%2$s</code>.',
				esc_html( $trimmed_meta_key ),
				esc_html( $meta_value )
			),
			array(
				$meta_key => $meta_value,
			),
			get_current_user_id()
		);
	}
}

/**
 * Record logs for events when new postmeta values are added (not changed).
 *
 * @param int    $meta_id    Unused. ID of updated metadata entry.
 * @param int    $object_id  Post ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Meta value.
 *
 * @return void
 */
function capture_added_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
	$post_type = get_post_type( $object_id );

	if ( Pledge\CPT_ID !== $post_type ) {
		return;
	}

	switch ( $meta_key ) {
		case PledgeMeta\META_PREFIX . 'pledge-email-confirmed':
			if ( true === $meta_value ) {
				add_log_entry(
					$object_id,
					'Pledge email address confirmed.',
					array(
						'email' => get_post_meta( $object_id, PledgeMeta\META_PREFIX . 'org-pledge-email', true )
					),
					get_current_user_id()
				);
			}
			break;
	}
}


function capture_transition_post_status( $new_status, $old_status, WP_Post $post ) {
	if ( Pledge\CPT_ID !== get_post_type( $post ) ) {
		return;
	}

	if ( $new_status === $old_status ) {
		return;
	}

	add_log_entry(
		$post->ID,
		sprintf(
			'Pledge status changed from <code>%1$s</code> to <code>%2$s</code>.',
			esc_html( $old_status ),
			esc_html( $new_status )
		),
		array(),
		get_current_user_id()
	);
}
