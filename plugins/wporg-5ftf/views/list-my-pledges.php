<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use WordPressDotOrg\FiveForTheFuture;
use WP_User, WP_Post;

/**
 * @var WP_User $user
 * @var array   $contributor_pending_posts
 * @var array   $contributor_publish_posts
 * @var WP_Post $contributor_post
 * @var string  $success_message
 * @var string  $pledge_url
 * @var array   $profile_data
 * @var array   $confirmed_pledge_ids
 */

$has_contributions = $contributor_pending_posts || $contributor_publish_posts;

$edit_link = sprintf(
	'<a aria-label="%1$s" href="https://profiles.wordpress.org/me/profile/edit/group/5/">%2$s</a>',
	__( 'edit hours pledged', 'wporg-5ftf' ),
	__( '(edit)', 'wporg-5ftf' )
);
?>

<?php if ( is_user_logged_in() ) : ?>

	<header class="my-pledges__header">
		<div class="my-pledges__avatar">
			<?php echo get_avatar( $user->ID, 96, 'blank', __( 'Your avatar', 'wporg-5ftf' ) ); ?>
		</div>

		<h1 class="my-pledges__title">
			<?php esc_html_e( 'My Pledges', 'wporg-5ftf' ); ?>
		</h1>

		<?php if ( $profile_data['hours_per_week'] && $profile_data['team_names'] ) : ?>
			<p class="my-pledges__dedication">
				<?php echo wp_kses_data( sprintf(
					/* translators: %1$s is the number of hours, %2$s is the number of organizations, and %3$s is an edit link. */
					_n(
						'Pledged <strong>%1$s hours a week</strong> %3$s across %2$s organization.',
						'Pledged <strong>%1$s hours a week</strong> %3$s across %2$s organizations.',
						count( $confirmed_pledge_ids ),
						'wporg-5ftf'
					),
					$profile_data['hours_per_week'],
					count( $confirmed_pledge_ids ),
					$edit_link
				) ); ?>
			</p>

		<?php else : ?>
			<div class="notice notice-warning notice-alt">
				<p>
					<?php echo wp_kses_data( sprintf(
						__( 'Please <a href="%s">update your profile</a> with the <strong>number of hours per week</strong> that you contribute, and the <strong>teams</strong> that you contribute to.', 'wporg-5ftf' ),
						'https://profiles.wordpress.org/me/profile/edit/group/5/'
					) ); ?>
				</p>
			</div>

		<?php endif; ?>
	</header>

	<?php if ( $success_message ) : ?>
		<div class="my-pledges__notice notice notice-success notice-alt">
			<p>
				<?php echo esc_html( $success_message ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $has_contributions ) : ?>

		<?php if ( $contributor_publish_posts ) : ?>

			<div class="my-pledges__list">
				<?php
				foreach ( $contributor_publish_posts as $contributor_post ) {
					$pledge = get_post( $contributor_post->post_parent );
					require FiveForTheFuture\get_views_path() . 'single-my-pledge.php';
				}
				?>
			</div>

		<?php endif; ?>

		<?php if ( $contributor_pending_posts ) : ?>

			<div class="my-pledges__list is-pending-list">
				<h2><?php esc_html_e( 'Pending Pledges', 'wporg-5ftf' ); ?></h2>

				<?php
				foreach ( $contributor_pending_posts as $contributor_post ) {
					$pledge = get_post( $contributor_post->post_parent );
					require FiveForTheFuture\get_views_path() . 'single-my-pledge.php';
				}
				?>
			</div>

		<?php endif; ?>

	<?php else : ?>

		<?php echo wp_kses_data( sprintf(
			__( 'You don’t currently have any sponsorships. If your employer is sponsoring part of your time to contribute to WordPress, please ask them to <a href="%s">submit a pledge</a> and list you as a contributor.', 'wporg-5ftf' ),
			esc_url( $pledge_url )
		) ); ?>

		<?php // todo add some resources here about how they can convince their boss to sponsor some of their time? ?>

	<?php endif; ?>

<?php else : ?>

	<header class="my-pledges__header">
		<div class="my-pledges__avatar">
			<?php echo get_avatar( 0, 96, 'mystery' ); ?>
		</div>

		<h1 class="my-pledges__title">
			<?php esc_html_e( 'My Pledges', 'wporg-5ftf' ); ?>
		</h1>
	</header>

	<div class="notice notice-error notice-alt">
		<p>
			<?php echo wp_kses_data( sprintf(
				__( 'Please <a href="%s">log in to your WordPress.org account</a> in order to view your pledges.', 'wporg-5ftf' ),
				esc_url( wp_login_url( get_permalink() ) )
			) ); ?>
		</p>
	</div>

<?php endif; ?>
