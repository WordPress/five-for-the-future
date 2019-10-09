<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php if ( ! is_front_page() ) : ?>
		<header class="entry-header">
			<?php if ( is_singular() ) : ?>

				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

			<?php else : ?>

				<a href="<?php the_permalink(); ?>">
					<?php the_title( '<h2 class="entry-title">', '</h2>' ); ?>
				</a>

			<?php endif; ?>
		</header><!-- .entry-header -->
	<?php endif; ?>

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages( array(
			'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'wporg-5ftf' ),
			'after'  => '</div>',
		) );
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php
		edit_post_link(
			sprintf(
				/* translators: %s: Name of current post */
				esc_html__( 'Edit %s', 'wporg-5ftf' ),
				the_title( '<span class="screen-reader-text">"', '"</span>', false )
			),
			'<span class="edit-link">',
			'</span>'
		);
		?>
	</footer><!-- .entry-footer -->
</article><!-- #post-## -->
