<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

defined( 'WPINC' ) || die();

?>
<script type="text/template" id="tmpl-5ftf-send-link-dialog">
	<div id="send-link-dialog-bg" class="pledge-dialog__background" hidden data-no-inert></div>
	<div id="send-link-dialog" role="dialog" class="pledge-dialog" hidden tabindex="-1" aria-label="<?php esc_attr_e( 'Request to edit this pledge', 'wporg-5ftf' ); ?>">
		<p>
			<?php esc_html_e( 'Only pledge admins can edit pledges.', 'wporg-5ftf' ); ?>
		</p>

		<p>
			<?php esc_html_e( "If you're the admin, enter your email address and a confirmation link will be sent to you.", 'wporg-5ftf' ); ?>
		</p>

		<form method="post">
			<input type="hidden" name="pledge_id" value="<?php echo esc_attr( get_post()->ID ); ?>" />

			<label for="pledge_admin_address">
				<?php esc_html_e( 'Email Address', 'wporg-5ftf' ); ?>
			</label>

			<input
				id="pledge_admin_address"
				name="pledge_admin_address"
				type="email"
				required
				value=""
			/>

			<div class="message"></div>

			<input
				type="submit"
				class="button"
				name="get_manage_pledge_link"
				value="<?php esc_attr_e( 'Submit', 'wporg-5ftf' ); ?>"
			/>
		</form>
		<button type="button" class="button button-link pledge-dialog__close" aria-label="Close"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>
	</div>
</script>
