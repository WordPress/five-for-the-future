<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $data */
/** @var bool  $readonly */
?>

<div class="form-field form-field__agree">
	<p>
		<?php
		printf(
			wp_kses_post( '
				I understand and agree to the <a href="%s">expectations</a> for
				inclusion in the Five for the Future acknowledgement program.
			' ),
			esc_url( get_permalink( get_page_by_path( 'expectations' ) ) ) // TODO Change this URL?
		);
		?>
	</p>

	<input
		type="checkbox"
		id="5ftf-pledge-agreement"
		name="pledge-agreement"
		required
		<?php checked( $data['pledge-agreement'] ); ?>
	/>
	<label for="5ftf-pledge-agreement">
		<?php esc_html_e( 'Yes', 'wordpressorg' ); ?>
	</label>
</div>
