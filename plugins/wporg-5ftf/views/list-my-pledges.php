<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use WP_User, WP_Post;

/**
 * @var WP_User $user
 * @var array   $contributor_posts
 * @var WP_Post $contributor_post
 * @var string  $success_message
 * @var string  $pledge_url
 */

?>

<?php if ( is_user_logged_in() ) : ?>

	<?php echo get_avatar( $user->ID, 96, 'blank', "{$user->login}'s avatar" ); ?>

	<p>Pledged 10 hours a week across two organizations</p> <?php // todo pull from profiles. ?>

	<?php if ( $success_message ) : ?>
		<div class="notice notice-success notice-alt">
			<p>
				<?php echo esc_html( $success_message ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $contributor_posts ) : ?>
		<?php foreach ( $contributor_posts as $contributor_post ) : ?>
			<?php $pledge = get_post( $contributor_post->post_parent ); ?>

			<?php echo get_the_post_thumbnail( $pledge->ID, 'pledge-logo' ); ?>
			<?php echo esc_html( $pledge->post_title ); ?>

			<form action="" method="post">
				<input type="hidden" name="contributor_post_id" value="<?php echo esc_attr( $contributor_post->ID ); ?>" />

				<p>
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
						<p>
							You confirmed this pledge on {date}
						</p>

						<?php wp_nonce_field( 'leave_organization' ); ?>

						<input
							type="submit"
							name="leave_organization"
							value="Leave Organization"
						/>

					<?php endif; ?>
				</p>
			</form>

		<?php endforeach; ?>

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
