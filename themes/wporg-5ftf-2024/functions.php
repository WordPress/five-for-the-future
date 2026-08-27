<?php

namespace WordPressDotOrg\Theme\FiveForTheFuture_2024;

use function WordPressDotOrg\FiveForTheFuture\PledgeMeta\get_pledge_meta;
use const WordPressDotOrg\FiveForTheFuture\Pledge\CPT_ID as PLEDGE_POST_TYPE;

require_once __DIR__ . '/inc/block-config.php';
require_once __DIR__ . '/inc/block-bindings.php';
require_once __DIR__ . '/inc/seo-social-meta.php';

// Block files.
require_once __DIR__ . '/src/my-pledge-list/index.php';
require_once __DIR__ . '/src/pledge-contributors/index.php';
require_once __DIR__ . '/src/pledge-edit-button/index.php';
require_once __DIR__ . '/src/pledge-teams/index.php';

/**
 * Actions and filters.
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
add_filter( 'the_content', __NAMESPACE__ . '\inject_pledge_content', PHP_INT_MAX );
add_filter( 'get_the_excerpt', __NAMESPACE__ . '\inject_pledge_content', PHP_INT_MAX );
add_filter( 'search_template_hierarchy', __NAMESPACE__ . '\use_archive_template' );
add_filter( 'body_class', __NAMESPACE__ . '\add_body_class' );
add_filter( 'wp_calculate_image_srcset', __NAMESPACE__ . '\modify_image_srcset', 10, 3 );

// Remove table of contents.
add_filter( 'wporg_handbook_toc_should_add_toc', '__return_false' );

/**
 * Enqueue scripts and styles.
 */
function enqueue_assets() {
	$asset_file = get_theme_file_path( 'build/style/index.asset.php' );
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	// The parent style is registered as `wporg-parent-2021-style`, and will be loaded unless
	// explicitly unregistered. We can load any child-theme overrides by declaring the parent
	// stylesheet as a dependency.

	$metadata = require $asset_file;
	wp_enqueue_style(
		'wporg-5ftf-2024',
		get_theme_file_uri( 'build/style/style-index.css' ),
		array( 'wporg-parent-2021-style', 'wporg-global-fonts' ),
		$metadata['version']
	);
}

/**
 * Replace the post content with the pledge description.
 *
 * @param string $content Content of the current post.
 *
 * @return string Updated content.
 */
function inject_pledge_content( $content ) {
	if ( PLEDGE_POST_TYPE !== get_post_type() ) {
		return $content;
	}

	$data = get_pledge_meta( get_the_ID() );

	// Re-sanitize at output, and run last (PHP_INT_MAX) so the block and shortcode parsers can't
	// re-expand this value into markup a wider allow-list would accept.
	$description = wp_kses_data( $data['org-description'] );

	// Restore the paragraph formatting core's wpautop no longer applies now that we run last.
	if ( 'the_content' === current_filter() ) {
		$description = wpautop( $description );
	}

	return $description;
}

/**
 * Switch to the archive.html template on search results.
 *
 * @param string[] $templates A list of template candidates, in descending order of priority.
 */
function use_archive_template( $templates ) {
	global $wp_query;

	if ( is_search() ) {
		array_unshift( $templates, 'archive-5ftf_pledge.html' );
	}

	return $templates;
}

/**
 * Add a class to body when the current page is in the menu.
 *
 * @param string[] $classes An array of body class names.
 *
 * @return string[]
 */
function add_body_class( $classes ) {
	global $wp;
	// Get the main menu using the hooked function.
	$menus    = Block_Config\add_site_navigation_menus( [] );
	$slug     = $wp->request;
	$has_page = array_filter(
		$menus['main'],
		function ( $item ) use ( $slug ) {
			return trim( $item['url'], '/' ) === $slug;
		}
	);
	if ( ! empty( $has_page ) ) {
		$classes[] = 'is-page-in-menu';
	}
	return $classes;
}

/**
 * Filter the featured image to show an avatar on the `my-pledges` page.
 *
 * @param string       $html              The post thumbnail HTML.
 * @param int          $post_id           The post ID.
 * @param int          $post_thumbnail_id The post thumbnail ID, or 0 if there isn't one.
 * @param string|int[] $size              Requested image size. Can be any registered image size name, or
 *                                        an array of width and height values in pixels (in that order).
 * @param string|array $attr              Query string or array of attributes.
 *
 * @return string Updated HTML.
 */
function swap_avatar_for_featured_image( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
	if ( is_page( 'my-pledges' ) && empty( $html ) ) {
		$attr_str = '';
		foreach ( $attr as $name => $value ) {
			$attr_str .= " $name=" . '"' . $value . '"';
		}
		$html = get_avatar( wp_get_current_user(), 110, 'mystery', '', array( 'extra_attr' => $attr_str ) );
	}
	return $html;
}
add_filter( 'post_thumbnail_html', __NAMESPACE__ . '\swap_avatar_for_featured_image', 10, 5 );

/**
 * Filter the sizes attributes for specific images.
 *
 * @param array  $sources    One or more arrays of source data to include in the 'srcset'.
 * @param array  $size_array An array of requested width and height values.
 * @param string $image_src  The 'src' of the image.
 *
 * @return array
 */
function modify_image_srcset( $sources, $size_array, $image_src ) {
	// The main header image is set with a fixed height, and when the smaller
	// screen sizes are used, it scales up to that height causing pixelation.
	if ( str_contains( $image_src, '5ftf-wordcamp.jpg' ) ) {
		// These sizes are smaller than 260px tall.
		unset( $sources[1024], $sources[768], $sources[300] );
	}

	return $sources;
}
