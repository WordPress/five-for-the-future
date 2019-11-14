<?php
namespace WordPressDotOrg\FiveForTheFuture\Contributor;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Pledge, XProfile };
use WP_Error, WP_Post, WP_User;

defined( 'WPINC' ) || die();

const SLUG    = 'contributor';
const SLUG_PL = 'contributors';
const CPT_ID  = FiveForTheFuture\PREFIX . '_' . SLUG;

add_action( 'init',                                      __NAMESPACE__ . '\register_custom_post_type', 0 );
add_filter( 'manage_edit-' . CPT_ID . '_columns',        __NAMESPACE__ . '\add_list_table_columns' );
add_action( 'manage_' . CPT_ID . '_posts_custom_column', __NAMESPACE__ . '\populate_list_table_columns', 10, 2 );
add_filter( 'wp_nav_menu_objects',                       __NAMESPACE__ . '\hide_my_pledges_when_logged_out', 10 );

add_shortcode( '5ftf_my_pledges', __NAMESPACE__ . '\render_my_pledges' );

/**
 * Register the post type(s).
 *
 * @return void
 */
function register_custom_post_type() {
	$labels = array(
		'name'                  => _x( 'Contributors', 'Pledges General Name', 'wporg' ),
		'singular_name'         => _x( 'Contributor', 'Pledge Singular Name', 'wporg' ),
		'menu_name'             => __( 'Five for the Future', 'wporg' ),
		'archives'              => __( 'Contributor Archives', 'wporg' ),
		'attributes'            => __( 'Contributor Attributes', 'wporg' ),
		'parent_item_colon'     => __( 'Parent Contributor:', 'wporg' ),
		'all_items'             => __( 'Contributors', 'wporg' ),
		'add_new_item'          => __( 'Add New Contributor', 'wporg' ),
		'add_new'               => __( 'Add New', 'wporg' ),
		'new_item'              => __( 'New Contributor', 'wporg' ),
		'edit_item'             => __( 'Edit Contributor', 'wporg' ),
		'update_item'           => __( 'Update Contributor', 'wporg' ),
		'view_item'             => __( 'View Contributor', 'wporg' ),
		'view_items'            => __( 'View Contributors', 'wporg' ),
		'search_items'          => __( 'Search Contributors', 'wporg' ),
		'not_found'             => __( 'Not found', 'wporg' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'wporg' ),
		'insert_into_item'      => __( 'Insert into contributor', 'wporg' ),
		'uploaded_to_this_item' => __( 'Uploaded to this contributor', 'wporg' ),
		'items_list'            => __( 'Contributors list', 'wporg' ),
		'items_list_navigation' => __( 'Contributors list navigation', 'wporg' ),
		'filter_items_list'     => __( 'Filter contributors list', 'wporg' ),
	);

	$args = array(
		'labels'              => $labels,
		'supports'            => array( 'title' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=' . Pledge\CPT_ID,
		'menu_position'       => 25,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'can_export'          => false,
		'taxonomies'          => array(),
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'page',
		'capabilities'        => array(
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap'        => true,
		'show_in_rest'        => false, // todo Maybe turn this on later.
	);

	register_post_type( CPT_ID, $args );
}

/**
 * Add columns to the Contributors list table.
 *
 * @param array $columns
 *
 * @return array
 */
function add_list_table_columns( $columns ) {
	$first = array_slice( $columns, 0, 2, true );
	$last  = array_slice( $columns, 2, null, true );

	$new_columns = array(
		'pledge' => __( 'Pledge', 'wporg' ),
	);

	return array_merge( $first, $new_columns, $last );
}

/**
 * Render content in the custom columns added to the Contributors list table.
 *
 * @param string $column
 * @param int    $post_id
 *
 * @return void
 */
function populate_list_table_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'pledge':
			$contributor = get_post( $post_id );
			$pledge      = get_post( $contributor->post_parent );

			if ( ! $pledge ) {
				esc_html_e( 'Unattached', 'wordpressorg' );
				break;
			}

			$pledge_name = get_the_title( $pledge );

			if ( current_user_can( 'edit_post', $pledge->ID ) ) {
				$pledge_name = sprintf(
					'<a href="%1$s">%2$s</a>',
					get_edit_post_link( $pledge ),
					esc_html( $pledge_name )
				);
			}

			echo wp_kses_post( $pledge_name );
			break;
	}
}

/**
 * Add one or more contributors to a pledge.
 *
 * Note that this does not validate whether a contributor's wporg username exists in the system.
 *
 * @param int   $pledge_id    The post ID of the pledge.
 * @param array $contributors Array of contributor wporg usernames.
 *
 * @return void
 */
function add_pledge_contributors( $pledge_id, $contributors ) {
	$results = array();

	foreach ( $contributors as $wporg_username ) {
		$args = array(
			'post_type'   => CPT_ID,
			'post_title'  => sanitize_user( $wporg_username ),
			'post_parent' => $pledge_id,
			'post_status' => 'pending',
		);

		$result = wp_insert_post( $args, true );

		$results[ $wporg_username ] = ( is_wp_error( $result ) ) ? $result->get_error_code() : $result;
	}

	/**
	 * Action: Fires when one or more contributors are added to a pledge.
	 *
	 * @param int   $pledge_id    The post ID of the pledge.
	 * @param array $contributors Array of contributor wporg usernames.
	 * @param array $results      Associative array, key is wporg username, value is post ID on success,
	 *                            or an error code on failure.
	 */
	do_action( FiveForTheFuture\PREFIX . '_add_pledge_contributors', $pledge_id, $contributors, $results );
}

/**
 * Remove a contributor post from a pledge.
 *
 * This wrapper function ensures we have a standardized way of removing a contributor that will still
 * transition a post status (see PledgeMeta\update_confirmed_contributor_count).
 *
 * @param int $contributor_post_id
 *
 * @return false|WP_Post|null
 */
function remove_contributor( $contributor_post_id ) {
	$pledge_id = get_post( $contributor_post_id )->post_parent;
	$result    = wp_trash_post( $contributor_post_id );

	/**
	 * Action: Fires when a contributor is removed from a pledge.
	 *
	 * @param int                $pledge_id
	 * @param int                $contributor_post_id
	 * @param WP_Post|false|null $result
	 */
	do_action( FiveForTheFuture\PREFIX . '_remove_contributor', $pledge_id, $contributor_post_id, $result );

	return $result;
}

/**
 * Get the contributor posts associated with a particular pledge post.
 *
 * @param int    $pledge_id The post ID of the pledge.
 * @param string $status    Optional. 'all', 'pending', or 'publish'.
 * @param int    $contributor_id Optional. Retrieve a specific contributor instead of all.
 *
 * @return array An array of contributor posts. If $status is set to 'all', will be
 *               a multidimensional array with keys for each status.
 */
function get_pledge_contributors( $pledge_id, $status = 'publish', $contributor_id = null ) {
	$args = array(
		'page_id'     => $contributor_id,
		'post_type'   => CPT_ID,
		'post_parent' => $pledge_id,
		'numberposts' => -1,
		'orderby'     => 'title',
		'order'       => 'asc',
	);

	if ( 'all' === $status ) {
		$args['post_status'] = array( 'pending', 'publish' );
	} else {
		$args['post_status'] = sanitize_key( $status );
	}

	$posts = get_posts( $args );

	if ( 'all' === $status ) {
		$initial = array(
			'publish' => array(),
			'pending' => array(),
		);

		if ( empty( $posts ) ) {
			$posts = $initial;
		} else {
			$posts = array_reduce( $posts, function( $carry, WP_Post $item ) {
				$carry[ $item->post_status ][] = $item;

				return $carry;
			}, $initial );
		}
	}

	return $posts;
}

/**
 * Get the contributor posts in the format used for the JS templates.
 *
 * @param int $pledge_id The post ID of the pledge.
 *
 * @return array An array of contributor data, ready to be used in the JS templates.
 */
function get_pledge_contributors_data( $pledge_id ) {
	$contrib_data = array();
	$contributors = get_pledge_contributors( $pledge_id, 'all' );

	foreach ( $contributors as $contributor_status => $group ) {
		$contrib_data[ $contributor_status ] = array_map(
			function( $contributor_post ) use ( $contributor_status, $pledge_id ) {
				$name        = $contributor_post->post_title;
				$contributor = get_user_by( 'login', $name );

				return [
					'pledgeId'      => $pledge_id,
					'contributorId' => $contributor_post->ID,
					'status'        => $contributor_status,
					'avatar'        => get_avatar( $contributor, 32 ),
					// @todo Add full name, from `$contributor`?
					'name'          => $name,
					'displayName'   => $contributor->display_name,
					'publishDate'   => get_the_date( '', $contributor_post ),
					'resendLabel'   => __( 'Resend Confirmation', 'wporg' ),
					'removeConfirm' => sprintf( __( 'Remove %s from this pledge?', 'wporg-5ftf' ), $name ),
					'removeLabel'   => sprintf( __( 'Remove %s', 'wporg' ), $name ),
				];
			},
			$group
		);
	}
	return $contrib_data;
}

/**
 * Get the user objects that correspond with pledge contributor posts.
 *
 * @param WP_Post[] $contributor_posts
 *
 * @return WP_User[]
 */
function get_contributor_user_objects( array $contributor_posts ) {
	return array_map( function( WP_Post $post ) {
		return get_user_by( 'login', $post->post_title );
	}, $contributor_posts );
}

/**
 * Only show the My Pledges menu to users who are logged in.
 *
 * @param array $menu_items
 *
 * @return array
 */
function hide_my_pledges_when_logged_out( $menu_items ) {
	if ( get_current_user_id() ) {
		return $menu_items;
	}

	foreach ( $menu_items as $key => $item ) {
		if ( home_url( 'my-pledges/' ) === $item->url ) {
			unset( $menu_items[ $key ] );
		}
	}

	return $menu_items;
}

/**
 * Render the My Pledges shortcode.
 *
 * @return string
 */
function render_my_pledges() {
	$user            = wp_get_current_user();
	$profile_data    = XProfile\get_contributor_user_data( $user->ID );
	$pledge_url      = get_permalink( get_page_by_path( 'for-organizations' ) );
	$success_message = process_my_pledges_form();

	$contributor_pending_posts = get_posts( array(
		'title'       => $user->user_login,
		'post_type'   => CPT_ID,
		'post_status' => array( 'pending' ),
		'numberposts' => 100,
	) );

	$contributor_publish_posts = get_posts( array(
		'title'       => $user->user_login,
		'post_type'   => CPT_ID,
		'post_status' => array( 'publish' ),
		'numberposts' => 100,
	) );

	$confirmed_pledge_ids = wp_list_pluck( $contributor_publish_posts, 'ID' );

	ob_start();
	require FiveForTheFuture\get_views_path() . 'list-my-pledges.php';
	return ob_get_clean();
}

/**
 * Process the My Pledges form.
 *
 * @return string
 */
function process_my_pledges_form() {
	$contributor_post_id = filter_input( INPUT_POST, 'contributor_post_id', FILTER_VALIDATE_INT );
	$unverified_nonce    = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	if ( empty( $contributor_post_id ) || empty( $unverified_nonce ) ) {
		return ''; // Return early, the form wasn't submitted.
	}

	$contributor_post = get_post( $contributor_post_id );
	if ( ! isset( $contributor_post->post_type ) || CPT_ID !== $contributor_post->post_type ) {
		return ''; // Return early, the form was submitted incorrectly.
	}

	$current_user = wp_get_current_user();
	if ( ! isset( $current_user->user_login ) || $contributor_post->post_title !== $current_user->user_login ) {
		return ''; // User doesn't have permission to update this.
	}

	$pledge     = get_post( $contributor_post->post_parent );
	$message    = '';
	$new_status = false;

	if ( filter_input( INPUT_POST, 'join_organization' ) ) {
		$nonce_action = 'join_decline_organization_' . $contributor_post_id;
		wp_verify_nonce( $unverified_nonce, $nonce_action ) || wp_nonce_ays( $nonce_action );

		$new_status = 'publish';
		$message    = "You have joined the pledge from {$pledge->post_title}.";

	} elseif ( filter_input( INPUT_POST, 'decline_invitation' ) ) {
		$nonce_action = 'join_decline_organization_' . $contributor_post_id;
		wp_verify_nonce( $unverified_nonce, $nonce_action ) || wp_nonce_ays( $nonce_action );

		$new_status = 'trash';
		$message    = "You have declined the pledge invitation from {$pledge->post_title}.";

	} elseif ( filter_input( INPUT_POST, 'leave_organization' ) ) {
		$nonce_action = 'leave_organization_' . $contributor_post_id;
		wp_verify_nonce( $unverified_nonce, $nonce_action ) || wp_nonce_ays( $nonce_action );

		$new_status = 'trash';
		$message    = "You have left the {$pledge->post_title} pledge.";
	}

	if ( 'publish' === $new_status && 'publish' !== $contributor_post->post_status ) {
		wp_update_post( array(
			'ID'          => $contributor_post->ID,
			'post_status' => $new_status,
		) );
	} elseif ( 'trash' === $new_status && 'trash' !== $contributor_post->post_status ) {
		remove_contributor( $contributor_post->ID );
	}

	return $message;
}
