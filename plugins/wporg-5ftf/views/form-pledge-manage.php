<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use function WordPressDotOrg\FiveForTheFuture\get_views_path;
use function WordPressDotOrg\FiveForTheFuture\Contributor\get_join_link;

/**
 * @var bool   $can_view_form
 * @var int    $pledge_id
 * @var string $auth_token
 */

require __DIR__ . '/partial-result-messages.php';

?>

<?php if ( true === $can_view_form ) : ?>

	<form class="pledge-form" id="5ftf-form-pledge-manage" action="" method="post" enctype="multipart/form-data">
		<input type="hidden" name="pledge_id" value="<?php echo absint( $pledge_id ); ?>" />
		<input type="hidden" name="auth_token" value="<?php echo esc_attr( $auth_token ); ?>" />

		<?php
		wp_nonce_field( 'manage_pledge_' . $pledge_id );

		require get_views_path() . 'inputs-pledge-org-info.php';
		require get_views_path() . 'inputs-pledge-org-email.php';
		?>

		<div class="wp-block-button">
			<input
				type="submit"
				class="button button-primary wp-block-button__link"
				id="5ftf-pledge-submit"
				name="action"
				value="<?php esc_attr_e( 'Update Pledge', 'wporg-5ftf' ); ?>"
			/>
		</div>

		<h2><?php esc_html_e( 'Contributors', 'wporg-5ftf' ); ?></h2>

		<?php if ( 'publish' === get_post_status( $pledge_id ) ) : ?>
			<div class="form-field">
				<label for="5ftf-pledge-join-link">
					<?php esc_html_e( 'Join link', 'wporg-5ftf' ); ?>
				</label>
				<input
					type="text"
					id="5ftf-pledge-join-link"
					value="<?php echo esc_url( get_join_link( $pledge_id ) ); ?>"
					aria-describedby="5ftf-pledge-join-link-help"
					readonly
				/>
				<p id="5ftf-pledge-join-link-help">
					<?php esc_html_e( 'Share this link privately with your contributors so they can join the pledge themselves. They still confirm it from their My Pledges page, and you can remove anyone who shouldn’t be listed.', 'wporg-5ftf' ); ?>
				</p>
				<div class="wp-block-button is-style-outline is-small">
					<button
						type="submit"
						class="button button-secondary wp-block-button__link"
						form="5ftf-form-pledge-reset-join-link"
					>
						<?php esc_html_e( 'Reset link', 'wporg-5ftf' ); ?>
					</button>
				</div>
			</div>
		<?php endif; ?>

		<?php require get_views_path() . 'manage-contributors.php'; ?>

	</form>

	<?php // Kept apart from the manage form, so resetting the link doesn't carry unsaved pledge edits along. ?>
	<form id="5ftf-form-pledge-reset-join-link" action="" method="post">
		<?php wp_nonce_field( 'reset_join_link_' . $pledge_id ); ?>
		<input type="hidden" name="action" value="reset-join-link" />
		<input type="hidden" name="auth_token" value="<?php echo esc_attr( $auth_token ); ?>" />
		<input type="hidden" name="pledge_id" value="<?php echo absint( $pledge_id ); ?>" />
	</form>

	<?php require get_views_path() . 'form-pledge-remove.php'; ?>

<?php endif; ?>
