<?php
/**
 * Display pledge data in the archive & single view.
 */

namespace WordPressdotorg\Five_for_the_Future\Theme;

use WordPressDotOrg\FiveForTheFuture\Contributor;
use WordPressDotOrg\FiveForTheFuture\PledgeMeta;

$data = array();

foreach ( PledgeMeta\get_pledge_meta_config() as $key => $config ) {
	$data[ $key ] = get_post_meta( get_the_ID(), PledgeMeta\META_PREFIX . $key, $config['single'] );
}

$contributors = Contributor\get_pledge_contributors( get_the_ID() );
$count        = count( $contributors );

$content = apply_filters( 'the_content', $data['org-description'] );

$contributor_title = sprintf(
	esc_html(
		_n( '%1$s has pledged %2$d contributor', '%1$s has pledged %2$d contributors', $count, 'wordpressorg' )
	),
	wp_kses_post( get_the_title() ),
	intval( $count )
);
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="entry-image">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="entry-image__logo">
				<?php the_post_thumbnail(); ?>
			</div>
		<?php else : ?>
			<div class="entry-image__placeholder"></div>
		<?php endif; ?>
	</div><!-- .post-thumbnail -->

	<header class="entry-header">
		<?php if ( is_singular() ) : ?>

			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

		<?php else : ?>

			<?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>

		<?php endif; ?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php
			echo wp_kses_post( $content );
		?>
		
		<div class="pledge-contributors">
			<?php /* phpcs:ignore -- escaped above */ ?>
			<h3><?php echo $contributor_title ?></h3>

			<?php
			foreach ( $contributors as $contrib_post ) {
				$contrib = get_user_by( 'login', $contrib_post->post_title );
				if ( $contrib ) {
					printf(
						'<span class="pledge-contributor__avatar">%s</span>',
						get_avatar( $contrib->user_email, 30, 'blank' )
					);
				}
			}
			?>
		</div><!-- .pledge-contributors -->

	</div><!-- .entry-content -->
</article><!-- #post-## -->
