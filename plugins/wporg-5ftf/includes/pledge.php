<?php
/**
 * This file handles the operations related to setting up a custom post type. We change change the filename,
 * namespace, etc. once we have a better idea of what the CPT will be called.
 */

namespace WordPressDotOrg\FiveForTheFuture\Pledge;

use WordPressDotOrg\FiveForTheFuture;
use WP_Error;
use const WordPressDotOrg\FiveForTheFuture\PledgeMeta\META_PREFIX;

defined( 'WPINC' ) || die();

const SLUG    = 'pledge';
const SLUG_PL = 'pledges';
const CPT_ID  = FiveForTheFuture\PREFIX . '_' . SLUG;

add_action( 'init', __NAMESPACE__ . '\register', 0 );
add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu' );
add_action( 'pre_get_posts', __NAMESPACE__ . '\filter_query' );

/**
 * Register all the things.
 *
 * @return void
 */
function register() {
	register_custom_post_type();
	register_custom_post_status();
}

/**
 * Adjustments to the Five for the Future admin menu.
 *
 * @return void
 */
function admin_menu() {
	// New pledges should only be created through the front end form.
	remove_submenu_page( 'edit.php?post_type=' . CPT_ID, 'post-new.php?post_type=' . CPT_ID );
}

/**
 * Register the post type(s).
 *
 * @return void
 */
function register_custom_post_type() {
	$labels = array(
		'name'                  => _x( 'Pledges', 'Pledges General Name', 'wporg' ),
		'singular_name'         => _x( 'Pledge', 'Pledge Singular Name', 'wporg' ),
		'menu_name'             => __( 'Five for the Future', 'wporg' ),
		'archives'              => __( 'Pledge Archives', 'wporg' ),
		'attributes'            => __( 'Pledge Attributes', 'wporg' ),
		'parent_item_colon'     => __( 'Parent Pledge:', 'wporg' ),
		'all_items'             => __( 'Pledges', 'wporg' ),
		'add_new_item'          => __( 'Add New Pledge', 'wporg' ),
		'add_new'               => __( 'Add New', 'wporg' ),
		'new_item'              => __( 'New Pledge', 'wporg' ),
		'edit_item'             => __( 'Edit Pledge', 'wporg' ),
		'update_item'           => __( 'Update Pledge', 'wporg' ),
		'view_item'             => __( 'View Pledge', 'wporg' ),
		'view_items'            => __( 'View Pledges', 'wporg' ),
		'search_items'          => __( 'Search Pledges', 'wporg' ),
		'not_found'             => __( 'Not found', 'wporg' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'wporg' ),
		'insert_into_item'      => __( 'Insert into pledge', 'wporg' ),
		'uploaded_to_this_item' => __( 'Uploaded to this pledge', 'wporg' ),
		'items_list'            => __( 'Pledges list', 'wporg' ),
		'items_list_navigation' => __( 'Pledges list navigation', 'wporg' ),
		'filter_items_list'     => __( 'Filter pledges list', 'wporg' ),
	);

	$args = array(
		'labels'              => $labels,
		'supports'            => array( 'title', 'thumbnail' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 25,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'can_export'          => false,
		'taxonomies'          => array(),
		'has_archive'         => SLUG_PL,
		'rewrite'             => array(
			'slug' => SLUG,
		),
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
		'show_in_rest'        => false, // todo Maybe turn this on later.
	);

	register_post_type( CPT_ID, $args );
}

/**
 * Register the post status(es).
 *
 * @return void
 */
function register_custom_post_status() {
	register_post_status(
		FiveForTheFuture\PREFIX . '-deactivated',
		array(
			'label'       => __( 'Deactivated', 'wporg' ),
			'label_count' => _n_noop( 'Deactivated <span class="count">(%s)</span>', 'Deactivated <span class="count">(%s)</span>', 'wporg' ),
			'public'      => false,
			'internal'    => false,
			'protected'   => true,
			CPT_ID        => true, // Custom parameter to streamline its use with the Pledge CPT.
		)
	);
}

/**
 * Create a new pledge post.
 *
 * @param string $name The name of the company to use as the post title.
 *
 * @return int|WP_Error Post ID on success. Otherwise WP_Error.
 */
function create_new_pledge( $name ) {
	$args = array(
		'post_type'   => CPT_ID,
		'post_title'  => $name,
		'post_status' => 'draft',
	);

	return wp_insert_post( $args, true );
}

/**
 * Filter query for archive & search pages to ensure we're only showing the expected data.
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 * @return void
 */
function filter_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Searching is restricted to pledges only.
	if ( $query->is_search ) {
		$query->set( 'post_type', CPT_ID );
	}

	// Use the custom order param to sort the archive page.
	if ( $query->is_archive && CPT_ID === $query->get( 'post_type' ) ) {
		$order = isset( $_GET['order'] ) ? $_GET['order'] : '';
		switch ( $order ) {
			case 'alphabetical':
				$query->set( 'orderby', 'name' );
				$query->set( 'order', 'ASC' );
				break;

			case 'contributors':
				$query->set( 'meta_key', META_PREFIX . 'org-number-employees' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
		}
	}
}
