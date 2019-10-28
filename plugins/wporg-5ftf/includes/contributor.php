<?php
namespace WordPressDotOrg\FiveForTheFuture\Contributor;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\Pledge;
use WP_Error, WP_Post, WP_User;

defined( 'WPINC' ) || die();

const SLUG    = 'contributor';
const SLUG_PL = 'contributors';
const CPT_ID  = FiveForTheFuture\PREFIX . '_' . SLUG;

add_action( 'init',                                      __NAMESPACE__ . '\register_custom_post_type', 0 );
add_filter( 'manage_edit-' . CPT_ID . '_columns',        __NAMESPACE__ . '\add_list_table_columns' );
add_action( 'manage_' . CPT_ID . '_posts_custom_column', __NAMESPACE__ . '\populate_list_table_columns', 10, 2 );

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
 * Create a contributor post as a child of a pledge post.
 *
 * @param string $wporg_username
 * @param int    $pledge_id
 *
 * @return int|WP_Error Post ID on success. Otherwise WP_Error.
 */
function create_new_contributor( $wporg_username, $pledge_id ) {
	$args = array(
		'post_type'   => CPT_ID,
		'post_title'  => sanitize_user( $wporg_username ),
		'post_parent' => $pledge_id,
		'post_status' => 'pending',
	);

	return wp_insert_post( $args, true );
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
	return wp_trash_post( $contributor_post_id );
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

	if ( 'all' === $status && ! empty( $posts ) ) {
		$initial = array(
			'publish' => array(),
			'pending' => array(),
		);

		$posts = array_reduce( $posts, function( $carry, WP_Post $item ) {
			$carry[ $item->post_status ][] = $item;

			return $carry;
		}, $initial );
	}

	return $posts;
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
