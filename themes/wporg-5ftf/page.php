<?php

namespace WordPressdotorg\Five_for_the_Future\Theme;

get_header(); ?>

	<main id="main" class="site-main" role="main">

		<?php while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'page' );
		endwhile;  ?>

	</main><!-- #main -->

<?php get_footer();
