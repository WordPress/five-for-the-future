<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $data */
/** @var bool  $readonly */
?>

<div class="form-field">
	<label for="5ftf-pledge-contributors">
		<?php esc_html_e( 'Contributor Usernames', 'wordpressorg' ); ?>
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
		<?php esc_html_e( 'Separate each WordPress.org username with a comma.', 'wordpressorg' ); ?>
	</p>
</div>
