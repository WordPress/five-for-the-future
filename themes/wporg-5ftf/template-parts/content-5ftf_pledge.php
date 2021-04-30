<?php
/**
 * Display pledge data in the archive & single view.
 */

namespace WordPressdotorg\Five_for_the_Future\Theme;

use WordPressDotOrg\FiveForTheFuture\{Contributor, PledgeMeta };

$pledge = get_post();
$data = array();

foreach ( PledgeMeta\get_pledge_meta_config() as $key => $config ) {
	$data[ $key ] = get_post_meta( get_the_ID(), PledgeMeta\META_PREFIX . $key, $config['single'] );
}

$contributors = Contributor\get_contributor_user_objects(
	Contributor\get_pledge_contributors( get_the_ID() )
);

$allowed_html = array_merge(
	wp_kses_allowed_html( 'data' ),
	array(
		'span' => array(
			'class' => true,
		),
	)
);

$more_text = sprintf(
	__( '&hellip; <a href="%1$s">continue reading <span class="screen-reader-text">%2$s</span></a>', 'wporg-5ftf' ),
	esc_url( get_permalink() ),
	esc_html( get_the_title() )
);

$content = apply_filters( 'the_content', $data['org-description'] );
$content = strip_tags( $content );
$content = wp_trim_words( $content, 55, $more_text );

$total_hours = $pledge->{ PledgeMeta\META_PREFIX . 'pledge-total-hours' };

$contributor_title = sprintf(
	esc_html(
		_n( '%1$s has pledged %2$d hour a week', '%1$s has pledged %2$d hours a week', $total_hours, 'wporg-5ftf' )
	),
	wp_kses_post( get_the_title() ),
	intval( $total_hours )
);
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="entry-image">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="entry-image__logo">
				<?php the_post_thumbnail( 'pledge-logo' ); ?>
			</div>
		<?php else : ?>
			<div class="entry-image__placeholder"></div>
		<?php endif; ?>
	</div><!-- .entry-image -->

	<header class="entry-header">
		<?php if ( is_singular() ) : ?>

			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

		<?php else : ?>

			<?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>

		<?php endif; ?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<p>
			<?php echo wp_kses( $content, $allowed_html ); ?>
		</p>

		<?php /* phpcs:ignore -- escaped above */ ?>
		<h3><?php echo $contributor_title ?></h3>

		<ul class="pledge-contributors">
			<?php
			foreach ( $contributors as $contrib_user ) {
				printf(
					'<li class="pledge-contributor__avatar">%s</li>',
					get_avatar( $contrib_user->user_email, 40, 'mystery', $contrib_user->display_name )
				);
			}
			?>
		</ul><!-- .pledge-contributors -->

	</div><!-- .entry-content -->
</article><!-- #post-## -->
