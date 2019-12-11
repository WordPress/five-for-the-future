<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

/**
 * @var WP_Post $contributor_post
 * @var bool    $has_profile_data
 * @var WP_Post $pledge
 */

?>

<div class="my-pledges__pledge">
	<div class="entry-image">
		<?php if ( has_post_thumbnail( $pledge ) ) : ?>
			<div class="entry-image__logo">
				<?php echo get_the_post_thumbnail( $pledge->ID, 'pledge-logo' ); ?>
			</div>
		<?php else : ?>
			<div class="entry-image__placeholder"></div>
		<?php endif; ?>
	</div><!-- .entry-image -->

	<div class="my-pledges__pledge-meta">
		<a class="my-pledges__pledge-title" href="<?php echo esc_url( get_permalink( $pledge->ID ) ); ?>">
			<?php echo esc_html( $pledge->post_title ); ?>
		</a>

		<p class="my-pledges__pledge-date">
			<?php
			if ( 'publish' === $contributor_post->post_status ) {
				echo esc_html( sprintf(
					__( 'You confirmed this pledge on %s', 'wporg-5ftf' ),
					date( get_option( 'date_format' ), strtotime( $contributor_post->post_date ) )
				) );
			} else {
				echo esc_html_e( 'This organization would like to pledge your time', 'wporg-5ftf' );
			}
			?>
		</p>
	</div>

	<div class="my-pledges__pledge-actions">
		<form action="" method="post">
			<input type="hidden" name="contributor_post_id" value="<?php echo esc_attr( $contributor_post->ID ); ?>" />

			<?php if ( 'pending' === $contributor_post->post_status ) : ?>
				<?php wp_nonce_field( 'join_decline_organization_' . $contributor_post->ID ); ?>

				<input
					type="submit"
					class="button button-default"
					name="join_organization"
					value="Join Organization"
					<?php if ( ! $has_profile_data ) : ?>
						disabled="disabled"
					<?php endif; ?>
				/>

				<input
					type="submit"
					class="button button-danger button-link"
					name="decline_invitation"
					value="Decline Invitation"
				/>

			<?php elseif ( 'publish' === $contributor_post->post_status ) : ?>
				<?php wp_nonce_field( 'leave_organization_' . $contributor_post->ID ); ?>

				<input
					type="submit"
					class="button button-danger"
					name="leave_organization"
					value="Leave Organization"
				/>

			<?php endif; ?>

		</form>
	</div>
</div>
