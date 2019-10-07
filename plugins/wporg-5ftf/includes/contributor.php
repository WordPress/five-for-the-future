<?php
namespace WordPressDotOrg\FiveForTheFuture\Pledge;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\Pledge;

defined( 'WPINC' ) || die();

const SLUG    = 'contributor';
const SLUG_PL = 'contributors';
const CPT_ID  = FiveForTheFuture\PREFIX . '_' . SLUG;

add_action( 'init', __NAMESPACE__ . '\register_custom_post_type', 0 );

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
		'all_items'             => __( 'All Contributors', 'wporg' ),
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
