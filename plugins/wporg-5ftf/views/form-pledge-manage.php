<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use function WordPressDotOrg\FiveForTheFuture\get_views_path;

/**
 * @var bool   $can_view_form
 * @var int    $verified_pledge_id
 * @var string $verified_auth_token
 */

require __DIR__ . '/partial-result-messages.php';

?>

<?php if ( true === $can_view_form ) : ?>

	<form class="pledge-form" id="5ftf-form-pledge-manage" action="" method="post" enctype="multipart/form-data">
		<input type="hidden" name="pledge_id" value="<?php echo absint( $verified_pledge_id ); ?>" />
		<input type="hidden" name="auth_token" value="<?php echo esc_attr( $verified_auth_token ); ?>" />

		<?php
		wp_nonce_field( 'manage_pledge_' . $verified_pledge_id );

		require get_views_path() . 'inputs-pledge-org-info.php';
		require get_views_path() . 'manage-contributors.php';
		require get_views_path() . 'inputs-pledge-org-email.php';
		// todo make all this DRY with form-pledge-new.php ?
			// don't want the checkbox agreement though
			// anything else to leave out?
		?>

		<div>
			<input
				type="submit"
				id="5ftf-pledge-submit"
				name="action"
				value="<?php esc_attr_e( 'Update Pledge', 'wporg' ); ?>"
			/>
		</div>
	</form>

<?php endif; ?>
