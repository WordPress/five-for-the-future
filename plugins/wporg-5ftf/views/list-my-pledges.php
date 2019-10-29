<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use WP_User, WP_Post;

/**
 * @var WP_User $user
 * @var array   $contributor_posts
 * @var WP_Post $contributor_post
 * @var string  $success_message
 * @var string  $pledge_url
 * @var array   $profile_data
 * @var array   $confirmed_pledge_ids
 */

?>

<?php if ( is_user_logged_in() ) : ?>

	<?php echo get_avatar( $user->ID, 96, 'blank', "{$user->login}'s avatar" ); ?>

	<p>
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

	<?php if ( $success_message ) : ?>
		<div class="notice notice-success notice-alt">
			<p>
				<?php echo esc_html( $success_message ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $contributor_posts ) : ?>

		<div class="fftf_pledges">
			<?php foreach ( $contributor_posts as $contributor_post ) : ?>
				<?php $pledge = get_post( $contributor_post->post_parent ); ?>

				<div class="fftf_pledge">
					<div class="pledge-logo-container">
						<?php echo get_the_post_thumbnail( $pledge->ID, 'pledge-logo' ); ?>
					</div>

					<div class="pledge-title">
						<a href="<?php echo esc_url( get_permalink( $pledge->ID ) ); ?>">
							<?php echo esc_html( $pledge->post_title ); ?>
						</a>

						<?php if ( 'publish' === $contributor_post->post_status ) : ?>
							<p>
								<?php esc_html_e( sprintf(
									__( 'You confirmed this pledge on %s', 'wporg-5ftf' ),
									date( get_option( 'date_format' ), strtotime( $contributor_post->post_date ) )
								) ); ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="pledge-actions">
						<form action="" method="post">
							<input type="hidden" name="contributor_post_id" value="<?php echo esc_attr( $contributor_post->ID ); ?>" />

							<?php if ( 'pending' === $contributor_post->post_status ) : ?>
								<?php wp_nonce_field( 'join_decline_organization' ); ?>

								<input
									type="submit"
									name="join_organization"
									value="Join Organization"
								/>

								<input
									type="submit"
									class="button-link"
									name="decline_invitation"
									value="Decline Invitation"
								/>

							<?php elseif ( 'publish' === $contributor_post->post_status ) : ?>
								<?php wp_nonce_field( 'leave_organization' ); ?>

								<input
									type="submit"
									name="leave_organization"
									value="Leave Organization"
								/>

							<?php endif; ?>

						</form>
					</div>
				</div>

			<?php endforeach; ?>
		</div>

	<?php else : ?>

		<p>You don't currently have any sponsorships. If your employer is sponsoring part of your time to contribute to WordPress, please ask them to <a href="<?php echo esc_url( $pledge_url ); ?>">submit a pledge</a> and list you as a contributor.</p>

		<?php // todo add some resources here about how they can convince their boss to sponsor some of their time? ?>

	<?php endif; ?>

<?php else : ?>

	<div class="notice notice-error notice-alt">
		<p>
			Please <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">log in to your WordPress.org account</a> in order to view your pledges.
		</p>
	</div>

<?php endif; ?>
