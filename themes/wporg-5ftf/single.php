<?php
namespace WordPressDotOrg\FiveForTheFuture\Theme;

$post_type = get_post_type(); // 5ftf_pledge

get_header(); ?>

	<main id="main" class="site-main" role="main">

		<?php while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/' . $post_type, 'single' );
		endwhile; ?>

	</main><!-- #main -->

<?php get_footer();
