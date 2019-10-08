<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $contributors */
/** @var array $data */
/** @var bool  $readonly */
?>

<?php if ( empty( $contributors ) ) : ?>

<div class="form-field">
	<label for="5ftf-pledge-contributors">
		<?php esc_html_e( 'Contributors', 'wordpressorg' ); ?>
	</label>
	<input
		type="text"
		class="large-text"
		id="5ftf-pledge-contributors"
		name="pledge-contributors"
		value=""
		required
	/>
	<p>
		<!-- Instructions for inputting wporg usernames -->
	</p>
</div>

<?php else : ?>

<div class="5ftf-contributors">

</div>

<?php endif; ?>
