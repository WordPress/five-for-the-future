<?php
/** @var bool $editable */
/** @var string $email */
/** @var bool $confirmed */
?>

<label for="5ftf-pledge-email" class="screen-reader-text">
	<?php esc_html_e( 'Email', 'wordpressorg' ); ?>
</label>

<input
	type="text"
	class="regular-text"
	id="5ftf-pledge-email"
	name="org-pledge-email"
	value="<?php echo esc_attr( $data['pledge-email'] ); ?>"
	required
	<?php echo ( $editable ) ? '' : 'readonly'; ?>
/>

<?php if ( $confirmed ) : ?>
	<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
	<?php esc_html_e( 'Confirmed', 'wporg' ); ?>
<?php else : ?>
	<span class="dashicons dashicons-warning" aria-hidden="true"></span>
	<?php esc_html_e( 'Unconfirmed', 'wporg' ); ?>
	<button class="button-secondary">
		<?php esc_html_e( 'Resend confirmation', 'wporg' ); ?>
	</button>
<?php endif; ?>
