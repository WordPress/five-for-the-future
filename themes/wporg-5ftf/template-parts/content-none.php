<section class="no-results not-found">
	<header class="page-header">
		<h1 class="page-title"><?php esc_html_e( 'Nothing Found', 'wporg-5ftf' ); ?></h1>
	</header><!-- .page-header -->

	<div class="page-content">
		<?php if ( is_search() ) : ?>

			<p>
				<?php esc_html_e( 'Sorry, but nothing matched your search terms.', 'wporg-5ftf' ); ?>
			</p>

			<p>
				<?php esc_html_e( 'Please try again with some different keywords.', 'wporg-5ftf' ); ?>
			</p>

			<?php get_search_form(); ?>

		<?php else : ?>

			<p>
				<?php esc_html_e( 'It seems we can&#8217;t find what you&#8217;re looking for. Perhaps searching can help.', 'wporg-5ftf' ); ?>
			</p>

			<?php get_search_form(); ?>

		<?php endif; ?>
	</div><!-- .page-content -->
</section><!-- .no-results -->
