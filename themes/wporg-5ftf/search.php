<?php get_header(); ?>
	<main id="main" class="site-main" role="main">

		<?php if ( have_posts() ) : ?>
			<header class="page-header">
				<h1 class="page-title">
					<?php
					printf(
						/* translators: Search query. */
						esc_html__( 'Showing results for: %s', 'wporg-5ftf' ),
						'<strong>' . get_search_query() . '</strong>'
					);
					?>
				</h1>
			</header><!-- .page-header -->

			<?php

			while ( have_posts() ) :
				the_post();

				get_template_part( 'template-parts/content', 'page' );
			endwhile;

			the_posts_pagination();

		else :

			get_template_part( 'template-parts/content', 'none' );

		endif; ?>
	</main><!-- #main -->

<?php get_footer();
