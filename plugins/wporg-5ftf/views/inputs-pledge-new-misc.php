<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var bool $editable */
/** @var array $data */
?>

<div class="form-field">
	<p>
		<!-- Statement of agreement to pledge, link to further info maybe? -->
	</p>

	<input
		type="checkbox"
		id="5ftf-pledge-agree"
		name="pledge-agree"
		required
	/>
	<label for="5ftf-pledge-agree">
		<?php esc_html_e( 'I agree', 'wordpressorg' ); ?>
	</label>
</div>
