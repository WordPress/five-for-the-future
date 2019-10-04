<?php

namespace WordPressdotorg\Five_for_the_Future\Theme;


/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function setup() {
	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	// Don't include Adjacent Posts functionality.
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );

	register_nav_menus( array(
		'primary' => esc_html__( 'Primary', 'wporg-5ftf' ),
	) );

	add_theme_support( 'wp4-styles' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'css/style-editor.css' );

	add_theme_support(
		'editor-color-palette',
		array(
			array(
				'name'  => 'W.org Blue',
				'slug'  => 'wporg-blue',
				'color' => '#1E8CBE',
			),
			array(
				'name'  => 'W.org Purple',
				'slug'  => 'wporg-purple',
				'color' => '#826EB4',
			),
			array(
				'name'  => 'W.org White',
				'slug'  => 'wporg-white',
				'color' => '#FFFFFF',
			),
		)
	);

	if ( function_exists( 'register_block_style' ) ) {
		register_block_style(
			'core/columns',
			array(
				'name'         => 'wporg-parallelogram',
				'label'        => __( 'Parallelogram', 'wporg-5ftf' ),
				'style_handle' => 'wporg-style',
			)
		);

		register_block_style(
			'core/paragraph',
			array(
				'name'         => 'wporg-tldr',
				'label'        => __( 'Summary paragraph', 'wporg-5ftf' ),
				'style_handle' => 'wporg-style',
			)
		);
	}

	// todo also setup block styles for other things, like the quote symbol, etc.
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function content_width() {
	$GLOBALS['content_width'] = apply_filters( 'wporg_plugins_content_width', 640 );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\content_width', 0 );

/**
 * Enqueue scripts and styles.
 */
function scripts() {
	wp_enqueue_style( 'wporg-style', get_theme_file_uri( '/css/style.css' ), [ 'dashicons', 'open-sans' ], '20190703' );

	wp_enqueue_script( 'wporg-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20181209', true );
	wp_enqueue_script( 'wporg-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\scripts' );

/**
 * Extend the default WordPress body classes.
 *
 * Adds classes to make it easier to target specific pages.
 *
 * @param array $classes Body classes.
 * @return array
 */
function body_class( $classes ) {
	if ( is_page() ) {
		$page = get_queried_object();

		$classes[] = 'page-' . $page->post_name;

		if ( $page->post_parent ) {
			$parent = get_post( $page->post_parent );

			$classes[] = 'page-parent-' . $parent->post_name;
		}
	}

	return array_unique( $classes );
}
add_filter( 'body_class', __NAMESPACE__ . '\body_class' );

/**
 * Filters the list of CSS body classes for the current post or page.
 *
 * @param array $classes An array of body classes.
 * @return array
 */
function custom_body_class( $classes ) {
	$classes[] = 'no-js';
	return $classes;
}
add_filter( 'body_class', __NAMESPACE__ . '\custom_body_class' );

/**
 * Filters an enqueued script & style's fully-qualified URL.
 *
 * @param string $src    The source URL of the enqueued script/style.
 * @param string $handle The style's registered handle.
 * @return string
 */
function loader_src( $src, $handle ) {
	$cdn_urls = [
		'dashicons',
		'wp-embed',
		'jquery-core',
		'jquery-migrate',
		'wporg-style',
		'wporg-navigation',
		'wporg-skip-link-focus-fix',
		'wporg-plugins-popover',
		'wporg-plugins-locale-banner',
		'wporg-plugins-stats',
		'wporg-plugins-client',
		'wporg-plugins-faq',
	];

	if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
		return $src;
	}

	// Use CDN url.
	if ( in_array( $handle, $cdn_urls, true ) ) {
		$src = str_replace( get_home_url(), 'https://s.w.org', $src );
	}

	// Remove version argument.
	if ( in_array( $handle, [ 'open-sans' ], true ) ) {
		$src = remove_query_arg( 'ver', $src );
	}

	return $src;
}
add_filter( 'style_loader_src', __NAMESPACE__ . '\loader_src', 10, 2 );
add_filter( 'script_loader_src', __NAMESPACE__ . '\loader_src', 10, 2 );
