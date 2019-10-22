<?php

namespace WordPressDotOrg\FiveForTheFuture\Theme;

get_header(); ?>

	<main id="main" class="site-main" role="main">

		<section class="error-404 not-found">
			<header class="page-header">
				<h1 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'wporg-5ftf' ); ?></h1>
			</header><!-- .page-header -->

			<div class="page-content">
				<p>
					<?php
					/* translators: URL to home page. */
					printf( wp_kses_post( __( 'Try searching from the field below, or go to the <a href="%s">home page</a>.', 'wporg-5ftf' ) ), esc_url( get_home_url() ) );
					?>
				</p>

				<p>
					<?php get_search_form(); ?>
				</p>

				<div class="logo-swing">
					<img src="<?php echo esc_url( get_theme_file_uri( '/images/wp-logo-blue-trans-blur.png' ) ); ?>" class="wp-logo" />
					<img src="<?php echo esc_url( get_theme_file_uri( '/images/wp-logo-blue.png' ) ); ?>" class="wp-logo" />
				</div>
			</div><!-- .page-content -->
		</section><!-- .error-404 -->

	</main><!-- #main -->

<?php
get_footer();
