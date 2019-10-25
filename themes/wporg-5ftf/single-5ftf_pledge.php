<?php
namespace WordPressDotOrg\FiveForTheFuture\Theme;

use WordPressDotOrg\FiveForTheFuture\Contributor;
use WordPressDotOrg\FiveForTheFuture\XProfile;
use WP_Post;

$contribution_data = XProfile\get_aggregate_contributor_data_for_pledge( get_the_ID() );

$contributors = Contributor\get_contributor_user_objects(
	// TODO set to 'publish' when finished testing.
	Contributor\get_pledge_contributors( get_the_ID(), 'pending' )
);

$report_page = get_page_by_path( 'report' );

get_header(); ?>

	<main id="main" class="site-main" role="main">

		<?php while ( have_posts() ) : the_post(); // phpcs:ignore ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<div class="">
					<?php the_title( '<h2 class="entry-title">', '</h2>' ); ?>
					<span>
						<?php
						printf(
							'<a href="%1$s">%1$s</a>',
							esc_url( $post->{'5ftf_org-url'} )
						);
						?>
					</span>
				</div>
				<?php the_post_thumbnail( array( 288, 288 ) ); ?>
			</header>

			<div class="entry-content">
				<h3><?php esc_html_e( 'About', 'wporg' ); ?></h3>

				<?php echo wp_kses_post( wpautop( $post->{'5ftf_org-description'} ) ); ?>

				<?php if ( ! empty( $contributors ) ) : ?>
					<h3><?php esc_html_e( 'Contributions', 'wporg' ); ?></h3>

					<p>
						<?php
						printf(
							wp_kses_post( __( '%1$s sponsors %2$s for a total of <strong>%3$s</strong> hours per week.', 'wporg' ) ),
							get_the_title(),
							sprintf(
								_n( '<strong>%d</strong> contributor', '<strong>%d</strong> contributors', $contribution_data['contributors'], 'wporg' ),
								number_format_i18n( absint( $contribution_data['contributors'] ) )
							),
							number_format_i18n( absint( $contribution_data['hours'] ) )
						);
						?>
					</p>
					<p>
						<?php
						printf(
							wp_kses_post( __( 'Contributors from %s work on the following teams:', 'wporg' ) ),
							get_the_title()
						);
						?>
					</p>
					<ul class="team-grid">
						<?php foreach ( $contribution_data['teams'] as $team ) :
							$badge_classes = get_badge_classes( $team );
							?>
							<li>
								<div class="badge item dashicons <?php echo esc_attr( implode( ' ', $badge_classes ) ); ?>"></div>
								<?php echo esc_html( $team ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<h3><?php esc_html_e( 'Contributors', 'wporg' ); ?></h3>

				<?php if ( ! empty( $contributors ) ) : ?>
					<ul class="contributor-grid">
						<?php foreach ( $contributors as $contributor ) : ?>
							<li>
								<?php echo get_avatar( $contributor->user_email, 280 ); ?>
								<?php
								printf(
									'<a href="%1$s">%2$s</a>',
									sprintf(
										'https://profiles.wordpress.org/%s/',
										sanitize_key( $contributor->user_login )
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
