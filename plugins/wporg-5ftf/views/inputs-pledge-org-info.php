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

<div class="form-field form-field__logo">
	<label for="5ftf-org-logo">
		<?php esc_html_e( 'Logo', 'wordpressorg' ); ?>
	</label>
	<br />
	<?php if ( is_admin() && has_post_thumbnail() ) : ?>
		<div class="form-field__logo-display">
			<?php the_post_thumbnail(); ?>
		</div>
	<?php endif; ?>
	<input
		type="file"
		id="5ftf-org-logo"
		name="org-logo"
	/>
</div>

<div class="form-field">
	<label for="5ftf-org-url">
		<?php esc_html_e( 'Website Address', 'wordpressorg' ); ?>
	</label>
	<input
		type="url"
		id="5ftf-org-url"
		name="org-url"
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

<div class="form-field">
	<label for="5ftf-org-number-employees">
		<?php esc_html_e( 'Number of Employees Being Contributed', 'wordpressorg' ); ?>
	</label>
	<input
		type="number"
		id="5ftf-org-number-employees"
		name="org-number-employees"
		value="<?php echo esc_attr( $data['org-number-employees'] ); ?>"
		required
		<?php echo $readonly ? 'readonly' : ''; ?>
	/>
</div>
