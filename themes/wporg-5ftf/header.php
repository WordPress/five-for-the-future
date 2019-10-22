<?php

namespace WordPressDotOrg\FiveForTheFuture\Theme;

global $wporg_global_header_options;
$GLOBALS['pagetitle'] = wp_get_document_title();

if ( ! isset( $wporg_global_header_options['in_wrapper'] ) ) {
	$wporg_global_header_options['in_wrapper'] = '';
}

$wporg_global_header_options['in_wrapper'] .= '<a class="skip-link screen-reader-text" href="#main">' . esc_html__( 'Skip to content', 'wporg-5ftf' ) . '</a>';

require WPORGPATH . 'header.php';

?>

<div id="page" class="site">
	<div id="content" class="site-content">
		<header id="masthead" class="site-header <?php echo is_front_page() ? 'home' : ''; ?>" role="banner">
			<div class="site-branding">
				<?php if ( is_front_page() ) : ?>
					<h1 class="site-title">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php echo esc_html_x( 'Five for the Future', 'Site title', 'wporg-5ftf' ); ?>
						</a>
					</h1>

					<p class="site-description">
						<?php esc_html_e( 'WordPress fuels more than a third of the web. Are you a part of it?', 'wporg-5ftf' ); ?>
					</p>

				<?php else : ?>

					<p class="site-title">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php echo esc_html_x( 'Five for the Future', 'Site title', 'wporg-5ftf' ); ?>
						</a>
					</p>

					<nav id="site-navigation" class="main-navigation" role="navigation">
						<button
							class="menu-toggle dashicons dashicons-arrow-down-alt2"
							aria-controls="primary-menu"
							aria-expanded="false"
							aria-label="<?php esc_attr_e( 'Primary Menu', 'wporg-5ftf' ); ?>"
						>
						</button>

						<div id="primary-menu" class="menu">
							<?php
							wp_nav_menu( array(
								'theme_location' => 'primary',
								'menu_id'        => 'primary-menu',
							) );
							?>
						</div>
					</nav><!-- #site-navigation -->
				<?php endif; ?>
			</div><!-- .site-branding -->
		</header><!-- #masthead -->
