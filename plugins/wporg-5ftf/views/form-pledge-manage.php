<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use function WordPressDotOrg\FiveForTheFuture\get_views_path;

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

		<div>
			<input
				type="submit"
				class="button button-primary"
				id="5ftf-pledge-submit"
				name="action"
				value="<?php esc_attr_e( 'Update Pledge', 'wporg-5ftf' ); ?>"
			/>
		</div>

		<h2><?php esc_html_e( 'Contributors', 'wporg-5ftf' ); ?></h2>

		<?php require get_views_path() . 'manage-contributors.php'; ?>

	</form>

<?php endif; ?>
