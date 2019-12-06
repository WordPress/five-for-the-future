<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

/**
 * @var bool   $can_view_form
 * @var int    $pledge_id
 * @var string $auth_token
 */

?>

<hr />

<form class="pledge-form" id="5ftf-form-pledge-remove" action="" method="post">
	<h2><?php esc_html_e( 'Remove Pledge', 'wporg-5ftf' ); ?></h2>

	<p>
		<?php esc_html_e( 'This will remove your pledge from the Five for the Future listing. You will not be able to reactivate it or submit a new pledge for this company.', 'wporg-5ftf' ); ?>
	</p>

	<p>
		<?php wp_nonce_field( 'remove_pledge_' . $pledge_id ); ?>
		<input type="hidden" name="action" value="remove-pledge" />
		<input type="hidden" name="auth_token" value="<?php echo esc_attr( $auth_token ); ?>" />
		<input type="hidden" name="pledge_id" value="<?php echo absint( $pledge_id ); ?>" />
		<button type="submit" class="button button-danger" id="5ftf-pledge-remove">
			<?php esc_html_e( 'Remove Pledge', 'wporg-5ftf' ); ?>
		</button>
	</p>
</form>
