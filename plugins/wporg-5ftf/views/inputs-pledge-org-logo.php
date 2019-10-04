<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var bool $editable */
/** @var array $data */
?>

<div class="form-field">
	<label for="5ftf-org-logo">
		<?php esc_html_e( 'Logo', 'wordpressorg' ); ?>
	</label>
	<input
		type="file"
		id="5ftf-org-logo"
		name="org-logo"
	/>
</div>
