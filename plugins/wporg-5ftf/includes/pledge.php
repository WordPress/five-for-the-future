<?php
/**
 * This file handles the operations related to setting up a custom post type. We change change the filename,
 * namespace, etc. once we have a better idea of what the CPT will be called.
 */

namespace WordPressDotOrg\FiveForTheFuture\Pledge;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Auth, Contributor, Email };
use WP_Error, WP_Query;

use const WordPressDotOrg\FiveForTheFuture\PledgeMeta\META_PREFIX;

defined( 'WPINC' ) || die();

const SLUG             = 'pledge';
const SLUG_PL          = 'pledges';
const CPT_ID           = FiveForTheFuture\PREFIX . '_' . SLUG;
const DEACTIVE_STATUS  = FiveForTheFuture\PREFIX . '-deactivated';

// Admin hooks.
add_action( 'init',          __NAMESPACE__ . '\register', 0 );
add_action( 'admin_menu',    __NAMESPACE__ . '\admin_menu' );
add_action( 'pre_get_posts', __NAMESPACE__ . '\filter_query' );
// List table columns.
add_filter( 'manage_edit-' . CPT_ID . '_columns',        __NAMESPACE__ . '\add_list_table_columns' );
add_action( 'manage_' . CPT_ID . '_posts_custom_column', __NAMESPACE__ . '\populate_list_table_columns', 10, 2 );
// Deactivate & reactivate handling.
add_filter( 'post_row_actions',            __NAMESPACE__ . '\add_row_action', 10, 2 );
add_action( 'post_action_deactivate',      __NAMESPACE__ . '\handle_activation_action', 10, 3 );
add_action( 'post_action_reactivate',      __NAMESPACE__ . '\handle_activation_action', 10, 3 );
add_action( 'admin_notices',               __NAMESPACE__ . '\action_success_message' );
add_filter( 'display_post_states',         __NAMESPACE__ . '\add_status_to_display', 10, 2 );
add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\inject_status_label' );

// Front end hooks.
add_action( 'wp_enqueue_scripts',  __NAMESPACE__ . '\enqueue_assets' );
add_action( 'pledge_footer',       __NAMESPACE__ . '\render_manage_link_request' );
add_action( 'wp_footer',           __NAMESPACE__ . '\render_js_templates' );

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
		'name'                  => _x( 'Pledges', 'Pledges General Name', 'wporg-5ftf' ),
		'singular_name'         => _x( 'Pledge', 'Pledge Singular Name', 'wporg-5ftf' ),
		'menu_name'             => __( 'Five for the Future', 'wporg-5ftf' ),
		'archives'              => __( 'Pledge Archives', 'wporg-5ftf' ),
		'attributes'            => __( 'Pledge Attributes', 'wporg-5ftf' ),
		'parent_item_colon'     => __( 'Parent Pledge:', 'wporg-5ftf' ),
		'all_items'             => __( 'Pledges', 'wporg-5ftf' ),
		'add_new_item'          => __( 'Add New Pledge', 'wporg-5ftf' ),
		'add_new'               => __( 'Add New', 'wporg-5ftf' ),
		'new_item'              => __( 'New Pledge', 'wporg-5ftf' ),
		'edit_item'             => __( 'Edit Pledge', 'wporg-5ftf' ),
		'update_item'           => __( 'Update Pledge', 'wporg-5ftf' ),
		'view_item'             => __( 'View Pledge', 'wporg-5ftf' ),
		'view_items'            => __( 'View Pledges', 'wporg-5ftf' ),
		'search_items'          => __( 'Search Pledges', 'wporg-5ftf' ),
		'not_found'             => __( 'Not found', 'wporg-5ftf' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'wporg-5ftf' ),
		'insert_into_item'      => __( 'Insert into pledge', 'wporg-5ftf' ),
		'uploaded_to_this_item' => __( 'Uploaded to this pledge', 'wporg-5ftf' ),
		'items_list'            => __( 'Pledges list', 'wporg-5ftf' ),
		'items_list_navigation' => __( 'Pledges list navigation', 'wporg-5ftf' ),
		'filter_items_list'     => __( 'Filter pledges list', 'wporg-5ftf' ),
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
		'capabilities'        => array(
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap'        => true,
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
	$label_count = _n_noop(
		'Deactivated <span class="count">(%s)</span>',
		'Deactivated <span class="count">(%s)</span>',
		'wporg-5ftf'
	);
	register_post_status(
		DEACTIVE_STATUS,
		array(
			'label'                  => __( 'Deactivated', 'wporg-5ftf' ),
			'label_count'            => $label_count,
			'public'                 => false,
			'internal'               => false,
			'protected'              => true,
			'show_in_admin_all_list' => false,
		)
	);
}

/**
 * Inject deactivate/reactivate actions into row actions.
 *
 * @return array An array of row action links.
 */
function add_row_action( $actions, $post ) {
	// Not a pledge, or can't edit the post.
	if ( CPT_ID !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}
	$post_type_object = get_post_type_object( $post->post_type );
	if ( DEACTIVE_STATUS === $post->post_status ) {
		$actions['reactivate'] = sprintf(
			'<a href="%s" style="color:#297531;" aria-label="%s">%s</a>',
			wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=reactivate', $post->ID ) ), 'reactivate-post_' . $post->ID ),
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'Reactivate pledge &#8220;%s&#8221;', 'wporg-5ftf' ), $post->post_title ) ),
			__( 'Reactivate', 'wporg-5ftf' )
		);
	} else {
		unset( $actions['trash'] );
		$actions['deactivate'] = sprintf(
			'<a href="%s" style="color:#dc3232;" aria-label="%s">%s</a>',
			wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=deactivate', $post->ID ) ), 'deactivate-post_' . $post->ID ),
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'Deactivate pledge &#8220;%s&#8221;', 'wporg-5ftf' ), $post->post_title ) ),
			__( 'Deactivate', 'wporg-5ftf' )
		);
	}
	return $actions;
}

/**
 * Trigger the post status change when deactivate or reactivate actions are seen.
 *
 * @return void
 */
function handle_activation_action( $post_id ) {
	$action = $_REQUEST['action'];
	if ( ! in_array( $action, [ 'deactivate', 'reactivate' ] ) ) {
		return;
	}

	$post = get_post( $post_id );
	if ( ! is_a( $post, 'WP_Post' ) || CPT_ID !== $post->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}

	$sendback = wp_get_referer();
	$sendback = remove_query_arg( [ 'deactivated', 'reactivated' ], $sendback );

	if ( 'deactivate' === $action ) {
		wp_update_post( array(
			'ID'          => $post_id,
			'post_status' => DEACTIVE_STATUS,
		) );
		wp_redirect( add_query_arg( 'deactivated', 1, $sendback ) );
		exit();
	} else {
		wp_update_post( array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		) );
		wp_redirect( add_query_arg( 'reactivated', 1, $sendback ) );
		exit();
	}
}

/**
 * Output success messages when a pledge is deactivated or reactivated.
 *
 * @return void
 */
function action_success_message() {
	if ( isset( $_GET['deactivated'] ) ) : ?>
	<div id="message" class="notice notice-success is-dismissable">
		<p><?php esc_html_e( 'Pledge deactivated.', 'wporg-5ftf' ); ?></p>
	</div>
	<?php elseif ( isset( $_GET['reactivated'] ) ) : ?>
	<div id="message" class="notice notice-success is-dismissable">
		<p><?php esc_html_e( 'Pledge reactivated.', 'wporg-5ftf' ); ?></p>
	</div>
	<?php endif;
}

/**
 * Add "Deactivated" to the list of post states (displayed on each post in the list table).
 *
 * @param string[] $post_states An array of post display states.
 * @param WP_Post  $post        The current post object.
 *
 * @return array The filtered list of post display states.
 */
function add_status_to_display( $post_states, $post ) {
	if ( isset( $_REQUEST['post_status'] ) ) {
		$showing_status = $_REQUEST['post_status'];
	} else {
		$showing_status = '';
	}

	$status = DEACTIVE_STATUS;
	if ( $showing_status !== $status && $status === $post->post_status ) {
		$post_states[ $status ] = _x( 'Deactivated', 'pledge label', 'wporg-5ftf' );
	}

	return $post_states;
}

/**
 * Use JS to replace the empty status label on deactivated pledges.
 *
 * @param WP_Post $post The current post object.
 *
 * @return void
 */
function inject_status_label( $post ) {
	if ( CPT_ID === $post->post_type && DEACTIVE_STATUS === $post->post_status ) : ?>
		<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {
			$("#post-status-display").text("Deactivated");
			$("#save-action").remove();
			$("#publishing-action").remove();
		} );
		</script>
		<div class="misc-pub-section misc-pub-visibility">
			<p><?php esc_html_e( 'This pledge is deactivated, it cannot be edited.', 'wporg-5ftf' ); ?></p>
		</div>
	<?php endif;
}

/**
 * Add columns to the Pledges list table.
 *
 * @param array $columns
 *
 * @return array
 */
function add_list_table_columns( $columns ) {
	$first = array_slice( $columns, 0, 2, true );
	$last  = array_slice( $columns, 2, null, true );

	$new_columns = array(
		'contributor_counts' => __( 'Contributors', 'wporg-5ftf' ),
		'domain'             => __( 'Domain', 'wporg-5ftf' ),
	);

	return array_merge( $first, $new_columns, $last );
}

/**
 * Render content in the custom columns added to the Pledges list table.
 *
 * @param string $column
 * @param int    $post_id
 *
 * @return void
 */
function populate_list_table_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'contributor_counts':
			$contribs    = Contributor\get_pledge_contributors( $post_id, 'all' );
			$confirmed   = sprintf(
				_n( '%s confirmed', '%s confirmed', count( $contribs['publish'] ), 'wporg-5ftf' ),
				number_format_i18n( count( $contribs['publish'] ) )
			);
			$unconfirmed = sprintf(
				_n( '%s unconfirmed', '%s unconfirmed', count( $contribs['pending'] ), 'wporg-5ftf' ),
				number_format_i18n( count( $contribs['pending'] ) )
			);
			printf( '%s<br />%s', esc_html( $confirmed ), esc_html( $unconfirmed ) );
			break;
		case 'domain':
			$domain = get_post_meta( $post_id, META_PREFIX . 'org-domain', true );
			echo esc_html( $domain );
			break;
	}
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

	$pledge_id = wp_insert_post( $args, true );
	// The pledge's meta data is saved at this point via `save_pledge_meta()`, which is a `save_post` callback.

	if ( ! is_wp_error( $pledge_id ) ) {
		Email\send_pledge_confirmation_email( $pledge_id, get_post()->ID );
	}

	return $pledge_id;
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

	$contributor_count_key = META_PREFIX . 'pledge-confirmed-contributors';
	$hours_count_key       = META_PREFIX . 'pledge-total-hours';

	// Set up meta queries to include the "valid pledge" check, added to both search and pledge archive requests.
	$meta_queries   = (array) $query->get( 'meta_query' );
	$meta_queries[] = array(
		'key'     => $contributor_count_key,
		'value'   => 0,
		'compare' => '>',
		'type'    => 'NUMERIC',
	);

	// Searching is restricted to pledges with contributors only.
	if ( $query->is_search ) {
		$query->set( 'post_type', CPT_ID );
		$query->set( 'meta_query', $meta_queries );
	}

	// Use the custom order param to sort the archive page.
	if ( $query->is_archive && CPT_ID === $query->get( 'post_type' ) ) {
		// Archives should only show pledges with contributors.
		$query->set( 'meta_query', $meta_queries );
		$order = isset( $_GET['order'] ) ? $_GET['order'] : '';

		switch ( $order ) {
			case 'alphabetical':
				$query->set( 'orderby', 'name' );
				$query->set( 'order', 'ASC' );
				break;

			case 'hours':
				$query->set( 'meta_key', $hours_count_key );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;

			default:
				$query->set( 'orderby', 'rand' );
				break;
		}
	}

	// todo remove this when `rand` pagination fixed
	// see https://github.com/WordPress/five-for-the-future/issues/70#issuecomment-549066883.
	$query->set( 'posts_per_page', 100 );
}

/**
 * Check a key value against existing pledges to see if one already exists.
 *
 * @param string $key               The value to match against other pledges.
 * @param string $key_type          The type of value being matched. `email` or `domain`.
 * @param int    $current_pledge_id Optional. The post ID of the pledge to compare against others.
 *
 * @return bool
 */
function has_existing_pledge( $key, $key_type, int $current_pledge_id = 0 ) {
	$args = array(
		'post_type'   => CPT_ID,
		'post_status' => array( 'draft', 'pending', 'publish' ),
	);

	switch ( $key_type ) {
		case 'email':
			$args['meta_query'] = array(
				array(
					'key'   => META_PREFIX . 'org-pledge-email',
					'value' => $key,
				),
			);
			break;
		case 'domain':
			$args['meta_query'] = array(
				array(
					'key'   => META_PREFIX . 'org-domain',
					'value' => $key,
				),
			);
			break;
	}

	if ( $current_pledge_id ) {
		$args['exclude'] = array( $current_pledge_id );
	}

	$matching_pledge = get_posts( $args );

	return ! empty( $matching_pledge );
}

/**
 * Enqueue assets for front-end management.
 *
 * @return void
 */
function enqueue_assets() {
	wp_register_script( 'wicg-inert', plugins_url( 'assets/js/inert.min.js', __DIR__ ), [], '3.0.0', true );

	if ( CPT_ID === get_post_type() ) {
		$ver = filemtime( FiveForTheFuture\PATH . '/assets/js/frontend.js' );
		wp_enqueue_script( '5ftf-frontend', plugins_url( 'assets/js/frontend.js', __DIR__ ), [ 'jquery', 'wp-a11y', 'wp-util', 'wicg-inert' ], $ver, true );

		$script_data = [
			'ajaxurl'   => admin_url( 'admin-ajax.php', 'relative' ), // The global ajaxurl is not set on the frontend.
			'pledgeId'  => get_the_ID(),
			'ajaxNonce' => wp_create_nonce( 'send-manage-email' ),
		];
		wp_add_inline_script(
			'5ftf-frontend',
			sprintf(
				'var FiveForTheFuture = JSON.parse( decodeURIComponent( \'%s\' ) );',
				rawurlencode( wp_json_encode( $script_data ) )
			),
			'before'
		);
	}
}

/**
 * Render the button to toggle the "Request Manage Email" dialog.
 *
 * @return void
 */
function render_manage_link_request() {
	require_once FiveForTheFuture\get_views_path() . 'button-request-manage-link.php';
}

/**
 * Render JS templates at the end of the page.
 *
 * @return void
 */
function render_js_templates() {
	if ( CPT_ID === get_post_type() ) {
		require_once FiveForTheFuture\get_views_path() . 'modal-request-manage-link.php';
	}
}
