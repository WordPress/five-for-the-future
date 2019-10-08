<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $data */
/** @var bool  $readonly */
?>

<div class="form-field">
	<label for="5ftf-pledge-email">
		<?php esc_html_e( 'Administrator Email', 'wordpressorg' ); ?>
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
		<?php esc_html_e( 'This email will be used to verify your organization’s contribution profile, and later manage any changes.', 'wordpressorg' ); ?>
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
