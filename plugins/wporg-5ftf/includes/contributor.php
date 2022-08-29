<?php
namespace WordPressDotOrg\FiveForTheFuture\Contributor;

use WordPressDotOrg\FiveForTheFuture;
use WordPressDotOrg\FiveForTheFuture\{ Email, Pledge, XProfile };
use WP_Error, WP_Post, WP_User;

defined( 'WPINC' ) || die();

const SLUG    = 'contributor';
const SLUG_PL = 'contributors';
const CPT_ID  = FiveForTheFuture\PREFIX . '_' . SLUG;
const INACTIVITY_THRESHOLD_MONTHS = 3;

add_action( 'init',                                      __NAMESPACE__ . '\register_custom_post_type', 0 );
add_action( 'init',                                      __NAMESPACE__ . '\schedule_cron_jobs' );
add_filter( 'manage_edit-' . CPT_ID . '_columns',        __NAMESPACE__ . '\add_list_table_columns' );
add_action( 'manage_' . CPT_ID . '_posts_custom_column', __NAMESPACE__ . '\populate_list_table_columns', 10, 2 );
add_filter( 'wp_nav_menu_objects',                       __NAMESPACE__ . '\hide_my_pledges_when_logged_out', 10 );
add_action( 'notify_inactive_contributors',              __NAMESPACE__ . '\notify_inactive_contributors' );

add_shortcode( '5ftf_my_pledges', __NAMESPACE__ . '\render_my_pledges' );

/**
 * Register the post type(s).
 *
 * @return void
 */
function register_custom_post_type() {
	$labels = array(
		'name'                  => _x( 'Contributors', 'Pledges General Name', 'wporg-5ftf' ),
		'singular_name'         => _x( 'Contributor', 'Pledge Singular Name', 'wporg-5ftf' ),
		'menu_name'             => __( 'Five for the Future', 'wporg-5ftf' ),
		'archives'              => __( 'Contributor Archives', 'wporg-5ftf' ),
		'attributes'            => __( 'Contributor Attributes', 'wporg-5ftf' ),
		'parent_item_colon'     => __( 'Parent Contributor:', 'wporg-5ftf' ),
		'all_items'             => __( 'Contributors', 'wporg-5ftf' ),
		'add_new_item'          => __( 'Add New Contributor', 'wporg-5ftf' ),
		'add_new'               => __( 'Add New', 'wporg-5ftf' ),
		'new_item'              => __( 'New Contributor', 'wporg-5ftf' ),
		'edit_item'             => __( 'Edit Contributor', 'wporg-5ftf' ),
		'update_item'           => __( 'Update Contributor', 'wporg-5ftf' ),
		'view_item'             => __( 'View Contributor', 'wporg-5ftf' ),
		'view_items'            => __( 'View Contributors', 'wporg-5ftf' ),
		'search_items'          => __( 'Search Contributors', 'wporg-5ftf' ),
		'not_found'             => __( 'Not found', 'wporg-5ftf' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'wporg-5ftf' ),
		'insert_into_item'      => __( 'Insert into contributor', 'wporg-5ftf' ),
		'uploaded_to_this_item' => __( 'Uploaded to this contributor', 'wporg-5ftf' ),
		'items_list'            => __( 'Contributors list', 'wporg-5ftf' ),
		'items_list_navigation' => __( 'Contributors list navigation', 'wporg-5ftf' ),
		'filter_items_list'     => __( 'Filter contributors list', 'wporg-5ftf' ),
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
 * Schedule cron jobs.
 *
 * This needs to run on the `init` action, because Cavalcade isn't fully loaded before that, and events
 * wouldn't be scheduled.
 *
 * @see https://dotorg.trac.wordpress.org/changeset/15351/
 */
function schedule_cron_jobs() {
	if ( ! wp_next_scheduled( 'notify_inactive_contributors' ) ) {
		wp_schedule_event( time(), 'hourly', 'notify_inactive_contributors' );
	}
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
		'pledge' => __( 'Pledge', 'wporg-5ftf' ),
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
				esc_html_e( 'Unattached', 'wporg-5ftf' );
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
 * @return array List of the new contributor post IDs, mapped from username => ID.
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

	return $results;
}

/**
 * Remove all of the contributors for the given pledge.
 *
 * Some contributors are sponsored by multiple companies. They'll have a `5ftf_contributor` post for each company,
 * but only the post associated with the given pledge should be removed.
 */
function remove_pledge_contributors( int $pledge_id ) : void {
	$contributors = get_pledge_contributors( $pledge_id, 'all' );

	foreach ( $contributors as $status_group ) {
		foreach ( $status_group as $contributor ) {
			remove_contributor( $contributor->ID );
		}
	}
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
	$contributor = get_post( $contributor_post_id );
	$old_status  = $contributor->post_status;
	$pledge_id   = $contributor->post_parent;
	$result      = wp_trash_post( $contributor_post_id );

	if ( $result && 'publish' === $old_status ) {
		Email\send_contributor_removed_email( $pledge_id, $contributor );
	}

	$has_additional_sponsors = get_posts( array(
		'post_type'   => CPT_ID,
		'title'       => $contributor->post_title,
		'post_status' => 'publish',
	) );

	// `pending` contributors never confirmed they were associated with the company, so their profile data isn't
	// tied to the pledge, and shouldn't be reset. If a user has multiple sponsors, we don't know which hours are
	// sponsored by which company, so just leave them all.
	if ( 'publish' === $old_status && ! $has_additional_sponsors ) {
		$user = get_user_by( 'login', $contributor->post_title );

		XProfile\reset_contribution_data( $user->ID );
	}

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
	if ( ! $pledge_id ) {
		return array();
	}
	$contrib_data = array();
	$contributors = get_pledge_contributors( $pledge_id, 'all' );

	foreach ( $contributors as $contributor_status => $group ) {
		$contrib_data[ $contributor_status ] = array_map(
			function( $contributor_post ) use ( $contributor_status, $pledge_id ) {
				$name        = $contributor_post->post_title;
				$contributor = get_user_by( 'login', $name );

				return array(
					'pledgeId'      => $pledge_id,
					'contributorId' => $contributor_post->ID,
					'status'        => $contributor_status,
					'avatar'        => get_avatar( $contributor, 32 ),
					// @todo Add full name, from `$contributor`?
					'name'          => $name,
					'displayName'   => $contributor->display_name,
					'publishDate'   => get_the_date( '', $contributor_post ),
					'resendLabel'   => __( 'Resend Confirmation', 'wporg-5ftf' ),
					'removeConfirm' => sprintf( __( 'Remove %s from this pledge?', 'wporg-5ftf' ), $name ),
					'removeLabel'   => sprintf( __( 'Remove %s', 'wporg-5ftf' ), $name ),
				);
			},
			$group
		);
	}
	return $contrib_data;
}

/**
 * Get the user objects that correspond with contributor posts.
 *
 * @see `get_contributor_user_ids()` for a similar function.
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
 * Get user IDs for the given `CPT_ID` posts.
 *
 * This is similar to `get_contributor_user_objects()`, but returns more specific data, and is more performant
 * with large data sets (e.g., with `get_snapshot_data()`) because there is 1 query instead of
 * `count( $contributor_posts )`.
 *
 * @param WP_Post[] $contributor_posts
 *
 * @return array
 */
function get_contributor_user_ids( $contributor_posts ) {
	global $wpdb;

	$usernames = wp_list_pluck( $contributor_posts, 'post_title' );

	/*
	 * Generate placeholders dynamically, so that each username will be quoted individually rather than as a
	 * single string.
	 *
	 * @see https://developer.wordpress.org/reference/classes/wpdb/prepare/#comment-1557
	 */
	$usernames_placeholders = implode( ', ', array_fill( 0, count( $usernames ), '%s' ) );

	$query = "
		SELECT id
		FROM $wpdb->users
		WHERE user_login IN( $usernames_placeholders )
	";

	$user_ids = $wpdb->get_col(
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- phpcs is confused by the variable, but it does correctly prepare.
		$wpdb->prepare( $query, $usernames )
	);

	$user_ids = array_map( 'absint', $user_ids );

	return $user_ids;
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
	$unverified_nonce    = filter_input( INPUT_POST, '_wpnonce', FILTER_UNSAFE_RAW );
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
		$message    = "You have joined the pledge from $pledge->post_title.";

	} elseif ( filter_input( INPUT_POST, 'decline_invitation' ) ) {
		$nonce_action = 'join_decline_organization_' . $contributor_post_id;
		wp_verify_nonce( $unverified_nonce, $nonce_action ) || wp_nonce_ays( $nonce_action );

		$new_status = 'trash';
		$message    = "You have declined the pledge invitation from $pledge->post_title.";

	} elseif ( filter_input( INPUT_POST, 'leave_organization' ) ) {
		$nonce_action = 'leave_organization_' . $contributor_post_id;
		wp_verify_nonce( $unverified_nonce, $nonce_action ) || wp_nonce_ays( $nonce_action );

		$new_status = 'trash';
		$message    = "You have left the $pledge->post_title pledge.";
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

/**
 * Ensure each item in a list of usernames is valid and corresponds to a user.
 *
 * @param string $contributors A comma-separated list of username strings.
 * @param int    $pledge_id    Optional. The ID of an existing pledge post that contributors are being added to.
 *
 * @return array|WP_Error An array of sanitized wporg usernames on success. Otherwise WP_Error.
 */
function parse_contributors( $contributors, $pledge_id = null ) {
	$invalid_contributors   = array();
	$duplicate_contributors = array();
	$sanitized_contributors = array();

	$contributors = str_replace( '@', '', $contributors );
	$contributors = explode( ',', $contributors );

	$existing_usernames = array();
	if ( $pledge_id ) {
		$pledge_contributors = get_pledge_contributors( $pledge_id, 'all' );
		$existing_usernames  = wp_list_pluck(
			$pledge_contributors['publish'] + $pledge_contributors['pending'],
			'post_title'
		);
	}

	foreach ( $contributors as $wporg_username ) {
		$sanitized_username = sanitize_user( $wporg_username );
		$user               = get_user_by( 'login', $sanitized_username );

		if ( ! $user instanceof WP_User ) {
			$user = get_user_by( 'slug', $sanitized_username );
		}

		if ( $user instanceof WP_User ) {
			if ( in_array( $user->user_login, $existing_usernames, true ) ) {
				$duplicate_contributors[] = $user->user_login;
				continue;
			}

			$sanitized_contributors[] = $user->user_login;
		} else {
			$invalid_contributors[] = $wporg_username;
		}
	}

	/* translators: Used between sponsor names in a list, there is a space after the comma. */
	$item_separator = _x( ', ', 'list item separator', 'wporg-5ftf' );

	if ( ! empty( $invalid_contributors ) ) {
		return new WP_Error(
			'invalid_contributor',
			sprintf(
				/* translators: %s is a list of usernames. */
				__( 'The following contributor usernames are not valid: %s', 'wporg-5ftf' ),
				implode( $item_separator, $invalid_contributors )
			)
		);
	}

	if ( ! empty( $duplicate_contributors ) ) {
		return new WP_Error(
			'duplicate_contributor',
			sprintf(
				/* translators: %s is a list of usernames. */
				__( 'The following contributor usernames are already associated with this pledge: %s', 'wporg-5ftf' ),
				implode( $item_separator, $duplicate_contributors )
			)
		);
	}

	if ( empty( $sanitized_contributors ) ) {
		return new WP_Error(
			'contributor_required',
			__( 'The pledge must have at least one contributor username.', 'wporg-5ftf' )
		);
	}

	$sanitized_contributors = array_unique( $sanitized_contributors );

	return $sanitized_contributors;
}

/**
 * Send an email to inactive contributors.
 */
function notify_inactive_contributors() : void {
	$contributors = get_inactive_contributor_batch();
	$contributors = prune_unnotifiable_xprofiles( $contributors );
	$contributors = add_user_data_to_xprofile( $contributors );
	$contributors = prune_unnotifiable_users( $contributors );

	// Limit to 25 emails per cron run, to avoid triggering spam filters.
	if ( count( $contributors ) > 25 ) {
		// Select different contributors each time, just in case something causes some to get stuck at the front
		// of their batch each time. For example, if the email always fails and they never get a
		// `5ftf_last_inactivity_email` value.
		shuffle( $contributors );
		$contributors = array_slice( $contributors, 0, 25 );
	}

	foreach ( $contributors as $contributor ) {
		notify_inactive_contributor( $contributor );
	}
}

/**
 * Get the next group of inactive contributors.
 */
function get_inactive_contributor_batch() : array {
	global $wpdb;

	$batch_size = 500; // This can be large because most users will be pruned later on.
	$offset     = absint( get_option( '5ftf_inactive_contributors_offset', 0 ) );

	$user_xprofiles = $wpdb->get_results( $wpdb->prepare( '
		SELECT user_id, GROUP_CONCAT( field_id ) AS field_ids, GROUP_CONCAT( value ) AS field_values
		FROM `bpmain_bp_xprofile_data`
		WHERE field_id IN ( %d, %d )
		GROUP BY user_id
		ORDER BY user_id ASC
		LIMIT %d
		OFFSET %d',
		XProfile\FIELD_IDS['hours_per_week'],
		XProfile\FIELD_IDS['team_names'],
		$batch_size,
		$offset
	) );

	if ( $user_xprofiles ) {
		// We haven't reached the end of the totals rows yet.
		update_option( '5ftf_inactive_contributors_offset', $offset + $batch_size, false );

	} else {
		// We're at the end of total rows with 0 remainder, so reset.
		delete_option( '5ftf_inactive_contributors_offset' );
		return array();
	}

	$field_names = array_flip( XProfile\FIELD_IDS );

	foreach ( $user_xprofiles as $user ) {
		$user->user_id = absint( $user->user_id );
		$fields        = explode( ',', $user->field_ids );
		$values        = explode( ',', $user->field_values );

		foreach ( $fields as $index => $id ) {
			$user->{$field_names[ $id ]} = maybe_unserialize( $values[ $index ] );
		}

		$user->hours_per_week = absint( $user->hours_per_week ?? 0 );
		$user->team_names     = (array) ( $user->team_names ?? array() );

		unset( $user->field_ids, $user->field_values ); // Remove the concatenated data now that it's exploded.
	}

	return $user_xprofiles;
}

/**
 * Prune xprofile rows for users who shouldn't be notified of their inactivity.
 */
function prune_unnotifiable_xprofiles( array $xprofiles ) : array {
	$notifiable_teams = array( 'Polyglots Team', 'Training Team' );

	foreach ( $xprofiles as $index => $xprofile ) {
		if ( $xprofile->hours_per_week <= 0 || empty( $xprofile->team_names ) ) {
			unset( $xprofiles[ $index ] );
			continue;
		}

		// Remove if not on a participating team.
		// This is temporary, and should be removed when all teams are participating.
		// See https://github.com/WordPress/five-for-the-future/issues/190.
		$on_notifiable_team = false;

		foreach ( $xprofile->team_names as $team ) {
			if ( in_array( $team, $notifiable_teams, true ) ) {
				$on_notifiable_team = true;
				break;
			}
		}

		if ( ! $on_notifiable_team ) {
			unset( $xprofiles[ $index ] );
			continue;
		}
	}

	return $xprofiles;
}

/**
 * Merge user data with xprofile data.
 */
function add_user_data_to_xprofile( array $xprofiles ) : array {
	global $wpdb;

	if ( empty( $xprofiles ) ) {
		return array();
	}

	$full_users      = array();
	$xprofiles       = array_column( $xprofiles, null, 'user_id' ); // Re-index for direct access.
	$user_ids        = array_keys( $xprofiles );
	$id_placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );

	// phpcs:disable -- `$id_placeholders` is safely created above.
	$established_users = $wpdb->get_results( $wpdb->prepare( "
		SELECT
			u.ID, u.user_email, u.user_registered, u.user_nicename,
			um.meta_keys, um.meta_values
		FROM `$wpdb->users` u
			LEFT JOIN (
				SELECT
					user_id,
					GROUP_CONCAT( meta_key ) AS meta_keys,
					GROUP_CONCAT( meta_value ) AS meta_values
				FROM `$wpdb->usermeta`
				WHERE
					user_id IN ( $id_placeholders ) AND
					meta_key IN ( 'last_logged_in', '5ftf_last_inactivity_email', 'first_name' )
				GROUP BY user_id
			) um ON u.ID = um.user_id
		WHERE u.ID IN ( $id_placeholders )
		ORDER BY u.ID",
		array_merge( $user_ids, $user_ids )
	) );
	// phpcs:enable

	foreach ( $established_users as $user ) {
		$full_user = array(
			'user_id'        => absint( $user->ID ),
			'user_email'     => $user->user_email,
			'user_registered' => intval( strtotime( $user->user_registered ) ),
			'hours_per_week' => $xprofiles[ $user->ID ]->hours_per_week,
			'user_nicename'  => $user->user_nicename,
		);

		if ( ! empty( $user->meta_keys ) ) {
			$keys   = explode( ',', $user->meta_keys );
			$values = explode( ',', $user->meta_values );

			foreach ( $keys as $index => $key ) {
				$full_user[ $key ] = maybe_unserialize( $values[ $index ] );
			}
		}

		$full_user['last_logged_in']             = intval( strtotime( $full_user['last_logged_in'] ?? '' ) ); // Convert `false` to `0`.
		$full_user['5ftf_last_inactivity_email'] = intval( $full_user['5ftf_last_inactivity_email'] ?? 0 );
		$full_user['team_names']                 = (array) maybe_unserialize( $xprofiles[ $user->ID ]->team_names );

		$full_users[] = $full_user;
	}

	return $full_users;
}

/**
 * Prune users who shouldn't be notified of their inactivity.
 */
function prune_unnotifiable_users( array $contributors ) : array {
	$inactivity_threshold = strtotime( INACTIVITY_THRESHOLD_MONTHS . ' months ago' );

	foreach ( $contributors as $index => $contributor ) {
		// Skip new users because they haven't had a chance to contribute yet.
		if ( $contributor['user_registered'] > $inactivity_threshold ) {
			unset( $contributors[ $index ] );
			continue;
		}

		if ( is_active( $contributor['last_logged_in'] ) ) {
			unset( $contributors[ $index ] );
			continue;
		}

		if ( $contributor['5ftf_last_inactivity_email'] > $inactivity_threshold ) {
			unset( $contributors[ $index ] );
			continue;
		}
	}

	return $contributors;
}

/**
 * Determine if a contributor is active or not.
 *
 * Currently this only tracks the last login, but in the future it will be expanded to be more granular.
 *
 * @link https://github.com/WordPress/five-for-the-future/issues/210
 */
function is_active( int $last_login ) : bool {
	$inactivity_threshold = strtotime( INACTIVITY_THRESHOLD_MONTHS . ' months ago' );

	return $last_login > $inactivity_threshold;
}

/**
 * Notify an inactive contributor.
 */
function notify_inactive_contributor( array $contributor ) : void {
	if ( ! Email\send_contributor_inactive_email( $contributor ) ) {
		return;
	}

	update_user_meta( $contributor['user_id'], '5ftf_last_inactivity_email', time() );
	bump_stats_extra( 'five-for-the-future', 'Sent Inactive Contributor Email' );
}
