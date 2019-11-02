<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $data */
/** @var bool  $readonly */
?>

<div class="form-field">
	<label for="5ftf-org-name">
		<?php esc_html_e( 'Organization Name', 'wordpressorg' ); ?>
	</label>
	<input
		type="text"
		id="5ftf-org-name"
		name="org-name"
		value="<?php echo esc_attr( $data['org-name'] ); ?>"
		required
		<?php echo $readonly ? 'readonly' : ''; ?>
	/>
</div>

<?php if ( ! is_admin() ) : ?>
	<div class="form-field form-field__logo">
		<label for="5ftf-org-logo">
			<?php esc_html_e( 'Logo', 'wordpressorg' ); ?>
		</label>
		<br />
		<input
			type="file"
			id="5ftf-org-logo"
			name="org-logo"
		/>
	</div>
<?php endif; ?>

<div class="form-field">
	<label for="5ftf-org-url">
		<?php esc_html_e( 'Website Address', 'wordpressorg' ); ?>
	</label>
	<input
		type="url"
		id="5ftf-org-url"
		name="org-url"
		placeholder="https://example.com"
		value="<?php echo esc_attr( $data['org-url'] ); ?>"
		required
		<?php echo $readonly ? 'readonly' : ''; ?>
	/>
</div>

<div class="form-field">
	<label for="5ftf-org-description">
		<?php esc_html_e( 'Organization Blurb', 'wordpressorg' ); ?>
	</label>
	<textarea
		id="5ftf-org-description"
		name="org-description"
		rows="5"
		required
		<?php echo $readonly ? 'readonly' : ''; ?>
	><?php /* phpcs:ignore -- php tags should be on the same line as textarea to prevent extra whitespace */
		echo esc_html( $data['org-description'] );
	/* phpcs:ignore */ ?></textarea>
</div>
