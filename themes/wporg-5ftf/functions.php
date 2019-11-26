<?php

namespace WordPressDotOrg\FiveForTheFuture\Theme;


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
	wp_enqueue_style( 'wporg-style', get_theme_file_uri( '/css/style.css' ), [ 'dashicons', 'open-sans' ], '20191126' );

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
		case 'accessibility':
			$classes = array( 'badge-accessibility', 'dashicons-universal-access' );
			break;

		case 'cli':
			$classes = array( 'badge-wp-cli', 'dashicons-arrow-right-alt2' );
			break;

		case 'community':
			$classes = array( 'badge-community', 'dashicons-groups' );
			break;

		case 'core':
			$classes = array( 'badge-code-committer', 'dashicons-editor-code' );
			break;

		case 'design':
			$classes = array( 'badge-design', 'dashicons-art' );
			break;

		case 'documentation':
			$classes = array( 'badge-documentation', 'dashicons-admin-page' );
			break;

		case 'hosting':
			$classes = array( 'badge-hosting', 'dashicons-cloud' );
			break;

		case 'marketing':
			$classes = array( 'badge-marketing', 'dashicons-format-status' );
			break;

		case 'meta':
			$classes = array( 'badge-meta', 'dashicons-networking' );
			break;

		case 'mobile':
			$classes = array( 'badge-mobile', 'dashicons-smartphone' );
			break;

		case 'plugins':
			$classes = array( 'badge-plugins-reviewer ', 'dashicons-admin-plugins' );
			break;

		case 'polyglots':
			$classes = array( 'badge-translation-editor', 'dashicons-translation' );
			break;

		case 'security':
			$classes = array( 'badge-security-team', 'dashicons-lock' );
			break;

		case 'support':
			$classes = array( 'badge-support', 'dashicons-format-chat' );
			break;

		case 'test':
			$classes = array( 'badge-test-team', 'dashicons-desktop' );
			break;

		case 'themes':
			$classes = array( 'badge-themes-reviewer', 'dashicons-admin-appearance' );
			break;

		case 'tide':
			$classes = array( 'badge-tide', 'dashicons-tide' );
			break;

		case 'training':
			$classes = array( 'badge-training', 'dashicons-welcome-learn-more' );
			break;

		case 'tv':
			$classes = array( 'badge-wordpress-tv', 'dashicons-video-alt2' );
			break;

		default:
			$classes = array();
			break;
	}

	return $classes;
}
