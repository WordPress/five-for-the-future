<?php

namespace WordPressDotOrg\FiveForTheFuture\Theme;

get_header(); ?>

	<main id="main" class="site-main" role="main">

	<?php if ( have_posts() ) :
		if ( is_home() && ! is_front_page() ) : ?>
			<header>
				<h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
			</header>
		<?php endif;

		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/plugin', 'index' );
		endwhile;

		the_posts_pagination();

		else :

			get_template_part( 'template-parts/content', 'none' );

	endif; ?>

	</main><!-- #main -->

<?php get_footer();
