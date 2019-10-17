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
		id="5ftf-pledge-contributors"
		name="pledge-contributors"
		value="<?php echo esc_attr( $data['pledge-contributors'] ); ?>"
		required
		aria-describedby="5ftf-pledge-contributors-help"
	/>
	<p id="5ftf-pledge-contributors-help">
		<?php esc_html_e( 'Separate each username with a comma.', 'wordpressorg' ); ?>
	</p>
</div>

<?php else : ?>

<div class="5ftf-contributors">

</div>

<?php endif; ?>
