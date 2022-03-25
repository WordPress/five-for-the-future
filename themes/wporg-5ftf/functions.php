<?php

namespace WordPressDotOrg\FiveForTheFuture\Theme;

// Temporary for local environments. Remove this when the new header launches.
// See https://github.com/WordPress/wporg-mu-plugins/issues/38
if ( ! defined( 'FEATURE_2021_GLOBAL_HEADER_FOOTER' ) ) {
	define( 'FEATURE_2021_GLOBAL_HEADER_FOOTER', false );
}

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

	add_theme_support( 'post-thumbnails' );
	add_image_size( 'pledge-logo', 660, 200 );

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

		// FYI, this is affected by https://github.com/WordPress/gutenberg/issues/16429.
		register_block_style(
			'core/pullquote',
			array(
				'name'         => 'wporg-home-pullquote',
				'label'        => __( 'Homepage Pullquote', 'wporg-5ftf' ),
				'style_handle' => 'wporg-style',
			)
		);

		$hero_properties = array(
			'name'         => 'wporg-hero',
			'label'        => __( 'Hero (full width on mobile)', 'wporg-5ftf' ),
			'style_handle' => 'wporg-style',
		);

		register_block_style( 'core/image', $hero_properties );
		register_block_style( 'core/group', $hero_properties );
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
	$GLOBALS['content_width'] = 640;
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\content_width', 0 );

/**
 * Enqueue scripts and styles.
 */
function scripts() {
	wp_enqueue_style(
		'wporg-style',
		get_theme_file_uri( '/css/style.css' ),
		[ 'dashicons', 'open-sans' ],
		filemtime( __DIR__ . '/css/style.css' )
	);

	wp_enqueue_script(
		'wporg-navigation',
		get_template_directory_uri() . '/js/navigation.js',
		array(),
		filemtime( get_template_directory() . '/js/navigation.js' ),
		true
	);
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
 * Swaps out the no-js for the js body class if the browser supports Javascript.
 */
function nojs_body_tag() {
	echo "<script>document.body.className = document.body.className.replace('no-js','js');</script>\n";
}
add_action( 'wp_body_open', __NAMESPACE__ . '\nojs_body_tag' );

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

/**
 * Determines the CSS classes for a given team badge.
 *
 * Based on the `wporg_profiles_get_association_classes` function in the profiles.wordpress.org theme.
 *
 * @param string $team
 *
 * @return array
 */
function get_badge_classes( $team ) {
	switch ( strtolower( $team ) ) {
		case 'accessibility team':
			$classes = array( 'badge-accessibility', 'dashicons-universal-access' );
			break;

		case 'wp-cli team':
			$classes = array( 'badge-wp-cli', 'dashicons-arrow-right-alt2' );
			break;

		case 'community team':
			$classes = array( 'badge-community', 'dashicons-groups' );
			break;

		case 'core team':
			$classes = array( 'badge-code-committer', 'dashicons-editor-code' );
			break;

		case 'design team':
			$classes = array( 'badge-design', 'dashicons-art' );
			break;

		case 'documentation team':
			$classes = array( 'badge-documentation', 'dashicons-admin-page' );
			break;

		case 'hosting team':
			$classes = array( 'badge-hosting', 'dashicons-cloud' );
			break;

		case 'marketing team':
			$classes = array( 'badge-marketing', 'dashicons-format-status' );
			break;

		case 'meta team':
			$classes = array( 'badge-meta', 'dashicons-networking' );
			break;

		case 'mobile team':
			$classes = array( 'badge-mobile', 'dashicons-smartphone' );
			break;

		case 'openverse team':
			$classes = array( 'badge-openverse', 'dashicons-search' );
			break;

		case 'polyglots team':
			$classes = array( 'badge-translation-editor', 'dashicons-translation' );
			break;

		case 'support team':
			$classes = array( 'badge-support', 'dashicons-format-chat' );
			break;

		case 'test team':
			$classes = array( 'badge-test-team', 'dashicons-desktop' );
			break;

		case 'themes team':
		case 'theme review team':
			$classes = array( 'badge-themes-reviewer', 'dashicons-admin-appearance' );
			break;

		case 'tide team':
			$classes = array( 'badge-tide', 'dashicons-tide' );
			break;

		case 'training team':
			$classes = array( 'badge-training', 'dashicons-welcome-learn-more' );
			break;

		case 'tv team':
			$classes = array( 'badge-wordpress-tv', 'dashicons-video-alt2' );
			break;

		default:
			$classes = array();
			break;
	}

	return $classes;
}
