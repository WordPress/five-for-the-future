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
$has_profile_data  = $profile_data['hours_per_week'] && $profile_data['team_names'];

?>

<?php if ( is_user_logged_in() ) : ?>

	<header class="my-pledges__header">
		<div class="my-pledges__avatar">
			<?php echo get_avatar( $user->ID, 96, 'blank', __( 'Your avatar', 'wporg-5ftf' ) ); ?>
		</div>

		<h1 class="my-pledges__title">
			<?php esc_html_e( 'My Pledges', 'wporg-5ftf' ); ?>
		</h1>

		<?php if ( $has_profile_data ) : ?>
			<p class="my-pledges__dedication">
				<?php echo esc_html( sprintf(
					_n(
						'Pledged %1$s hours a week across %2$s organization',
						'Pledged %1$s hours a week across %2$s organizations',
						count( $confirmed_pledge_ids ),
						'wporg-5ftf'
					),
					$profile_data['hours_per_week'],
					count( $confirmed_pledge_ids )
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

				<?php if ( ! $has_profile_data ) : ?>
					<div class="notice notice-error notice-alt">
						<p>
							<?php echo wp_kses_data( sprintf(
								__( 'You need to <a href="%s">update your profile</a> before joining an organization.', 'wporg-5ftf' ),
								'https://profiles.wordpress.org/me/profile/edit/group/5/'
							) ); ?>
						</p>
					</div>
				<?php endif; ?>

				<?php
				foreach ( $contributor_pending_posts as $contributor_post ) {
					$pledge = get_post( $contributor_post->post_parent );
					require FiveForTheFuture\get_views_path() . 'single-my-pledge.php';
				}
				?>
			</div>

		<?php endif; ?>

	<?php else : ?>

		<p>You don't currently have any sponsorships. If your employer is sponsoring part of your time to contribute to WordPress, please ask them to <a href="<?php echo esc_url( $pledge_url ); ?>">submit a pledge</a> and list you as a contributor.</p>

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
			Please <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">log in to your WordPress.org account</a> in order to view your pledges.
		</p>
	</div>

<?php endif; ?>
