<?php
/**
 * Set up configuration for dynamic blocks.
 */

namespace WordPressDotOrg\Theme\FiveForTheFuture_2024\Block_Config;

/**
 * Actions and filters.
 */
add_filter( 'wporg_block_navigation_menus', __NAMESPACE__ . '\add_site_navigation_menus' );
add_filter( 'wporg_query_total_label', __NAMESPACE__ . '\update_query_total_label', 10, 2 );
add_filter( 'wporg_query_filter_options_sort', __NAMESPACE__ . '\get_sort_options' );
add_action( 'wporg_query_filter_in_form', __NAMESPACE__ . '\inject_other_filters' );
add_filter( 'render_block_core/post-excerpt', __NAMESPACE__ . '\inject_excerpt_more_link', 10, 3 );

/**
 * Provide a list of local navigation menus.
 */
function add_site_navigation_menus( $menus ) {
	global $wp_query;

	$is_handbook = function_exists( 'wporg_is_handbook' ) && wporg_is_handbook();

	$menu = array();

	$menu[] = array(
		'label' => __( 'For individuals', 'wporg-5ftf' ),
		'url'   => '/for-individuals/',
	);
	$menu[] = array(
		'label' => __( 'For organizations', 'wporg-5ftf' ),
		'url'   => '/for-organizations/',
	);
	$menu[] = array(
		'label' => __( 'Handbook', 'wporg-5ftf' ),
		'url'   => '/handbook/',
		'className' => $is_handbook ? 'current-menu-item' : '',
	);
	$menu[] = array(
		'label' => __( 'Pledges', 'wporg-5ftf' ),
		'url'   => '/pledges/',
	);
	$menu[] = array(
		'label' => __( 'Sponsorships', 'wporg-5ftf' ),
		'url'   => '/sponsorships/',
	);
	$menu[] = array(
		'label' => __( 'Contact', 'wporg-5ftf' ),
		'url'   => '/contact/',
	);
	$menu[] = array(
		'label'     => __( 'My pledges', 'wporg-5ftf' ),
		'url'       => '/my-pledges/',
		'className' => 'has-separator',
	);
	if ( ! is_user_logged_in() ) {
		global $wp;
		$redirect_url = home_url( $wp->request );
		$menu[] = array(
			'label' => __( 'Log in', 'wporg-5ftf' ),
			'url' => wp_login_url( $redirect_url ),
		);
	}

	return array(
		'main' => $menu,
	);
}

/**
 * Update the query total label to reflect "pledges" found.
 *
 * @param string $label       The maybe-pluralized label to use, a result of `_n()`.
 * @param int    $found_posts The number of posts to use for determining pluralization.
 *
 * @return string Updated string with total placeholder.
 */
function update_query_total_label( $label, $found_posts ) {
	/* translators: %s: the result count. */
	return _n( '%s pledge', '%s pledges', $found_posts, 'wporg-5ftf' );
}

/**
 * Provide a list of sort options.
 *
 * @param array $options The options for this filter.
 * @return array New list of category options.
 */
function get_sort_options( $options ) {
	global $wp_query;
	$sort = isset( $_GET['order'] ) ? $_GET['order'] : '';

	$label = __( 'Sort: Random', 'wporg-5ftf' );
	switch ( $sort ) {
		case 'alphabetical':
			$label = __( 'Sort: Alphabetical', 'wporg-5ftf' );
			break;
		case 'contributors':
			$label = __( 'Sort: Total Contributors', 'wporg-5ftf' );
			break;
		case 'hours':
			$label = __( 'Sort: Total Hours', 'wporg-5ftf' );
			break;
	}

	return array(
		'label'    => $label,
		'title'    => __( 'Sort', 'wporg-5ftf' ),
		'key'      => 'order',
		'action'   => home_url( '/pledges/' ),
		'options'  => array(
			''             => __( 'Random', 'wporg-5ftf' ),
			'alphabetical' => __( 'Alphabetical', 'wporg-5ftf' ),
			'contributors' => __( 'Total Contributors', 'wporg-5ftf' ),
			'hours'        => __( 'Total Hours', 'wporg-5ftf' ),
		),
		'selected' => [ $sort ],
	);
}

/**
 * Add in the search term as a hidden input in the filter form.
 *
 * This enables the sort filter to apply to search results, as opposed to
 * clearing the search when selected.
 *
 * @param string $key The key for the current filter.
 */
function inject_other_filters( $key ) {
	global $wp_query;

	// Pass through search query.
	if ( isset( $wp_query->query['s'] ) ) {
		printf( '<input type="hidden" name="s" value="%s" />', esc_attr( $wp_query->query['s'] ) );
	}
}

/**
 * Update the excerpt block content, replacing the placeholder string with
 * dynamic text including the pledge title for unique link text.
 *
 * @param string   $block_content The block content.
 * @param array    $block         The full block, including name and attributes.
 * @param WP_Block $instance      The block instance.
 *
 * @return string Updated block content.
 */
function inject_excerpt_more_link( $block_content, $block, $instance ) {
	$more_text = sprintf(
		__( 'Continue reading<span class="screen-reader-text"> %s</span>', 'wporg-5ftf' ),
		get_the_title( $instance->context['postId'] )
	);
	return str_replace( '[MORE]', $more_text,  $block_content );
}
