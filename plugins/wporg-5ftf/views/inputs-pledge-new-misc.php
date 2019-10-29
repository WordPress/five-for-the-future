<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $data */
/** @var bool  $readonly */
?>

<div class="form-field form-field__agree">
	<input
		type="checkbox"
		id="5ftf-pledge-agreement"
		name="pledge-agreement"
		required
		<?php checked( $data['pledge-agreement'] ); ?>
	/>
	<label for="5ftf-pledge-agreement">
		<?php
		printf(
			wp_kses_post( __( 'I understand and agree to the <a href="%s">expectations for inclusion</a> in the Five for the Future acknowledgement program.', 'wporg-5ftf' ) ),
			esc_url( get_permalink( get_page_by_path( 'expectations' ) ) ) // TODO Change this URL?
		);
		?>
	</label>
</div>
