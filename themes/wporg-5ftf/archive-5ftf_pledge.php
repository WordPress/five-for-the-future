<?php

namespace WordPressdotorg\Five_for_the_Future\Theme;

use const WordPressDotOrg\FiveForTheFuture\Pledge\CPT_ID;

// If we don't have any posts to display for the archive, then send a 404 status. See #meta4151.
if ( ! have_posts() ) {
	status_header( 404 );
	nocache_headers();
}

$pledge_order = isset( $_GET['order'] ) ? $_GET['order'] : '';

get_header(); ?>

	<main id="main" class="site-main" role="main">

	<?php if ( have_posts() ) : ?>

		<header class="page-header">
			<h1 class="page-title"><?php esc_html_e( 'Pledges', 'wordpressorg' ); ?></h1>
			<div class="page-header-callout">
				<a class="button" href="/for-organizations/" >
					<?php esc_html_e( 'Pledge your company', 'wordpressorg' ); ?>
				</a>
			</div>

			<div class="page-header-controls">
				<form method="get" action="<?php echo esc_url( get_post_type_archive_link( CPT_ID ) ); ?>">
					<label for="pledge-sort"><?php esc_html_e( 'Sort pledges by', 'wordpressorg' ); ?></label>
					<select class="custom-select" id="pledge-sort" name="order">
						<option value="" <?php selected( $pledge_order, '' ); ?>>
							<?php esc_html_e( 'All Pledges', 'wordpressorg' ); ?>
						</option>
						<option value="alphabetical" <?php selected( $pledge_order, 'alphabetical' ); ?>>
							<?php esc_html_e( 'Alphabetical', 'wordpressorg' ); ?>
						</option>
						<option value="contributors" <?php selected( $pledge_order, 'contributors' ); ?>>
							<?php esc_html_e( 'Total Contributors', 'wordpressorg' ); ?>
						</option>
					</select>
					<span class="screen-reader-text">
						<input type="submit" />
					</span>
				</form>

				<?php get_search_form(); ?>
			</div>
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

	<script type="text/javascript">
		jQuery( "#pledge-sort" ).change( function( event ) {
			jQuery( event.target ).closest( 'form' ).submit();
		} );
	</script>
<?php
get_footer();
