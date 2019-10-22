<?php
namespace WordPressDotOrg\FiveForTheFuture\Theme;

use WordPressDotOrg\FiveForTheFuture\Contributor;
use WP_Post;

/** @var WP_Post $post */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( '<h2 class="entry-title">', '</h2>' ); ?>
		<span>
			<?php
			printf(
				'<a href="%1$s">%1$s</a>',
				esc_url( $post->{'5ftf_org-url'} )
			);
			?>
		</span>
		<!-- TODO logo -->
	</header>

	<div class="entry-content">
		<h3><?php esc_html_e( 'About', 'wporg' ); ?></h3>

		<?php echo wp_kses_post( wpautop( $post->{'5ftf_org-description'} ) ) ?>

		<h3><?php esc_html_e( 'Contributions', 'wporg' ); ?></h3>

		<!-- TODO Pull info from xprofile -->

		<h3><?php esc_html_e( 'Contributors', 'wporg' ); ?></h3>

		<ul class="contributor-grid">
			<?php foreach ( Contributor\get_pledge_contributors( get_the_ID(), 'publish' ) as $contributor_post ) :
				$contributor = get_user_by( 'login', $contributor_post->post_title );
				?>
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
	</div>

	<footer class="entry-footer">
		<!-- TODO Determine URL for reporting a pledge -->
		<a href="#"><?php esc_html_e( 'Report a problem', 'wporg' ); ?></a>
	</footer>
</article>
