<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var bool $editable */
/** @var array $data */
?>

<div class="form-field">
	<label for="5ftf-org-name">
		<?php esc_html_e( 'Organization Name', 'wordpressorg' ); ?>
	</label>
	<input
		type="text"
		class="large-text"
		id="5ftf-org-name"
		name="org-name"
		value="<?php echo esc_attr( $data['org-name'] ); ?>"
		required
		<?php echo ( $editable ) ? '' : 'readonly'; ?>
	/>
</div>

<div class="form-field">
	<label for="5ftf-org-url">
		<?php esc_html_e( 'Website Address', 'wordpressorg' ); ?>
	</label>
	<input
		type="url"
		class="large-text"
		id="5ftf-org-url"
		name="org-url"
		value="<?php echo esc_attr( $data['org-url'] ); ?>"
		required
		<?php echo ( $editable ) ? '' : 'readonly'; ?>
	/>
</div>

<div class="form-field">
	<label for="5ftf-org-description">
		<?php _e( 'Organization Blurb', 'wordpressorg' ); ?>
	</label>
	<textarea
		class="large-text"
		id="5ftf-org-description"
		name="org-description"
		required
		<?php echo ( $editable ) ? '' : 'readonly'; ?>
	>
		<?php echo esc_html( $data['org-description'] ); ?>
	</textarea>
</div>
