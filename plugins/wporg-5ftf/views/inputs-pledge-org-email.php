<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use WP_Post;

/**
 * @var array   $data
 * @var bool    $readonly
 * @var WP_Post $pledge
 */

?>

<div class="form-field">
	<label for="5ftf-pledge-email">
		<?php esc_html_e( 'Administrator Email Address', 'wporg-5ftf' ); ?>
	</label>
	<input
		type="email"
		id="5ftf-pledge-email"
		name="org-pledge-email"
		placeholder="wordpress-contributors@example.com"
		value="<?php echo esc_attr( $data['org-pledge-email'] ); ?>"
		required
		aria-describedby="5ftf-pledge-email-help"
		<?php echo $readonly ? 'readonly' : ''; ?>
	/>
	<p id="5ftf-pledge-email-help">
		<?php esc_html_e( 'This address will be used to confirm your organization’s contribution profile, and later manage any changes. Please make sure that it’s a group address (e.g., wp-contributors@example.com) so that it persists across employee transitions.', 'wporg-5ftf' ); ?>
	</p>

	<?php if ( is_admin() ) : ?>
		<?php if ( $data['pledge-email-confirmed'] ) : ?>
			<p class="email-status is-confirmed">
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<?php esc_html_e( 'Confirmed', 'wporg-5ftf' ); ?>
			</p>
		<?php else : ?>
			<p class="email-status is-unconfirmed">
				<span class="dashicons dashicons-warning" aria-hidden="true"></span>
				<?php esc_html_e( 'Unconfirmed', 'wporg-5ftf' ); ?>
			</p>
			<?php submit_button(
				'Resend Confirmation',
				'secondary',
				'resend-pledge-confirmation',
				false,
				array( 'formaction' => add_query_arg( 'resend-pledge-id', $pledge->ID ) )
			); ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
