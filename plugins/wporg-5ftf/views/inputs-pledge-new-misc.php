<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $data */
/** @var bool  $readonly */
?>

<div class="form-field form-field__agree">
	<p>
		<!-- Statement of agreement to pledge, link to further info maybe? -->
	</p>

	<input
		type="checkbox"
		id="5ftf-pledge-agreement"
		name="pledge-agreement"
		required
		<?php checked( $data['pledge-agreement'] ); ?>
	/>
	<label for="5ftf-pledge-agreement">
		<?php esc_html_e( 'I agree', 'wordpressorg' ); ?>
	</label>
</div>
