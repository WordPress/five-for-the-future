<?php

namespace WordPressDotOrg\FiveForTheFuture\Theme;

get_header(); ?>

	<main id="main" class="site-main" role="main">

		<?php while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/plugin', 'single' );
		endwhile; ?>

	</main><!-- #main -->

<?php get_footer();
