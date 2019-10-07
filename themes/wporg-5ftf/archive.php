<?php

namespace WordPressdotorg\Five_for_the_Future\Theme;

// If we don't have any posts to display for the archive, then send a 404 status. See #meta4151.
if ( ! have_posts() ) {
	status_header( 404 );
	nocache_headers();
}

get_header(); ?>

	<main id="main" class="site-main" role="main">

	<?php if ( have_posts() ) : ?>

		<header class="page-header">
			<?php
			the_archive_title( '<h1 class="page-title">', '</h1>' );
			the_archive_description( '<div class="taxonomy-description">', '</div>' );
			?>
		</header><!-- .page-header -->

		<?php

		while ( have_posts() ) :
			the_post();

			/*
			 * Include the Post-Format-specific template for the content.
			 * If you want to override this in a child theme, then include a file
			 * called content-___.php (where ___ is the Post Format name) and that will be used instead.
			 */
			get_template_part( 'template-parts/content', 'page' );

		endwhile;

		the_posts_pagination();

	else :

		get_template_part( 'template-parts/content', 'none' );

	endif;
	?>

	</main><!-- #main -->

<?php
get_footer();
