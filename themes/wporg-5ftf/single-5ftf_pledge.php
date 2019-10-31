<?php
namespace WordPressDotOrg\FiveForTheFuture\Theme;

use WordPressDotOrg\FiveForTheFuture\Contributor;
use WordPressDotOrg\FiveForTheFuture\XProfile;
use WP_Post;

use const WordPressDotOrg\FiveForTheFuture\PledgeMeta\META_PREFIX;

$contribution_data = XProfile\get_aggregate_contributor_data_for_pledge( get_the_ID() );

$contributors = Contributor\get_contributor_user_objects(
	Contributor\get_pledge_contributors( get_the_ID(), 'publish' )
);

$report_page = get_page_by_path( 'report' );

get_header(); ?>

	<main id="main" class="site-main" role="main">

		<?php while ( have_posts() ) : the_post(); // phpcs:ignore ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<div>
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
					<span class="pledge-url">
						<?php
						printf(
							'<a href="%1$s">%1$s</a>',
							esc_url( $post->{ META_PREFIX . 'org-url' } )
						);
						?>
					</span>
				</div>
				<div class="entry-image">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="entry-image__logo">
							<?php the_post_thumbnail( 'pledge-logo' ); ?>
						</div>
					<?php else : ?>
						<div class="entry-image__placeholder"></div>
					<?php endif; ?>
				</div><!-- .entry-image -->
			</header>

			<div class="entry-content">
				<h2><?php esc_html_e( 'About', 'wporg' ); ?></h2>

				<div class="pledge-company-description">
					<?php echo wpautop( wp_kses_data( $post->{ META_PREFIX . 'org-description' } ) ); ?>
				</div>

				<?php if ( ! empty( $contributors ) ) : ?>
					<h2><?php esc_html_e( 'Contributions', 'wporg' ); ?></h2>

					<p>
						<?php
						echo wp_kses_post( sprintf(
							__( '%1$s sponsors %2$s for a total of <strong>%3$s</strong> hours per week.', 'wporg' ),
							get_the_title(),
							sprintf(
								_n( '<strong>%d</strong> contributor', '<strong>%d</strong> contributors', $contribution_data['contributors'], 'wporg' ),
								number_format_i18n( absint( $contribution_data['contributors'] ) )
							),
							number_format_i18n( absint( $contribution_data['hours'] ) )
						) );
						?>
					</p>
					<p>
						<?php
						echo wp_kses_post( sprintf(
							__( 'Contributors from %s work on the following teams:', 'wporg' ),
							get_the_title()
						) );
						?>
					</p>
					<ul class="team-grid">
						<?php foreach ( $contribution_data['teams'] as $team ) :
							$badge_classes = get_badge_classes( $team );
							?>
							<li>
								<div class="badge item dashicons <?php echo esc_attr( implode( ' ', $badge_classes ) ); ?>"></div>
								<span class="badge-label"><?php echo esc_html( $team ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<h2><?php esc_html_e( 'Contributors', 'wporg' ); ?></h2>

				<?php if ( ! empty( $contributors ) ) : ?>
					<ul class="pledge-contributors has-contrib-names <?php echo ( 2 === count( $contributors ) ) ? 'has-2-contribs' : ''; ?>">
						<?php foreach ( $contributors as $contributor ) : ?>
							<li class="pledge-contributor">
								<span class="pledge-contributor__avatar">
									<?php echo get_avatar( $contributor->user_email, 280 ); ?>
								</span>
								<?php
								printf(
									'<a class="pledge-contributor__name" href="%1$s">%2$s</a>',
									sprintf(
										'https://profiles.wordpress.org/%s/',
										sanitize_key( $contributor->user_nicename ) // phpcs:ignore WordPress.Security.EscapeOutput -- sanitize_key will catch any security issues.
									),
									esc_html( $contributor->display_name )
								);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p><?php esc_html_e( 'No confirmed contributors yet.', 'wporg' ); ?></p>
				<?php endif; ?>
			</div>

			<footer class="entry-footer">
				<a href="<?php the_permalink( $report_page ); ?>"><?php esc_html_e( 'Report a problem', 'wporg' ); ?></a>
			</footer>
		</article>

		<?php endwhile; ?>

	</main><!-- #main -->

<?php get_footer();
