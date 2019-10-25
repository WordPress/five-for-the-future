<?php

namespace WordPressDotOrg\FiveForTheFuture\Theme;

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

			get_template_part( 'template-parts/content', get_post_type() );

		endwhile;

		the_posts_pagination();

		?>

	<?php else :

		get_template_part( 'template-parts/content', 'none' );

	endif; ?>

	</main><!-- #main -->

<?php
get_footer();
