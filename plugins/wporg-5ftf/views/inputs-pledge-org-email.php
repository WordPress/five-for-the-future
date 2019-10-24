<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $data */
/** @var bool  $readonly */
?>

<div class="form-field">
	<label for="5ftf-pledge-email">
		<?php esc_html_e( 'Administrator Email Address', 'wordpressorg' ); ?>
	</label>
	<input
		type="email"
		id="5ftf-pledge-email"
		name="org-pledge-email"
		value="<?php echo esc_attr( $data['org-pledge-email'] ); ?>"
		required
		aria-describedby="5ftf-pledge-email-help"
		<?php echo $readonly ? 'readonly' : ''; ?>
	/>
	<p id="5ftf-pledge-email-help">
		<?php esc_html_e( "This address will be used to confirm your organization’s contribution profile, and later manage any changes. Please make sure that it's a group address (e.g., wp-contributors@example.com) so that it persists across employee transitions.", 'wordpressorg' ); ?>
	</p>

	<?php if ( is_admin() ) : ?>
		<?php if ( $data['pledge-email-confirmed'] ) : ?>
			<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			<?php esc_html_e( 'Confirmed', 'wporg' ); ?>
		<?php else : ?>
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<?php esc_html_e( 'Unconfirmed', 'wporg' ); ?>
			<button class="button-secondary">
				<?php esc_html_e( 'Resend confirmation', 'wporg' ); ?>
			</button>
		<?php endif; ?>
	<?php endif; ?>
</div>
