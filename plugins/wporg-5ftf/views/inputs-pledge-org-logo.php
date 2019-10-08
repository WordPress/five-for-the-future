<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $data */
/** @var bool  $readonly */
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
