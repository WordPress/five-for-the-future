<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

/**
 * @var array  $data
 * @var bool   $readonly
 * @var string $action
 */

$updating = in_array( $action, array( 'manage_pledge', 'Update Pledge' ) );
$required = $updating ? '' : 'required';

$label = $updating
	? __( 'Add New Contributors', 'wordpressorg' )
	: __( 'Contributor Usernames', 'wordpressorg' )
;

?>

<div class="form-field">
	<label for="5ftf-pledge-contributors">
		<?php echo esc_html( $label ); ?>
	</label>
	<input
		type="text"
		id="5ftf-pledge-contributors"
		name="pledge-contributors"
		placeholder="sanguine.zoe206, captain-mal, kayleefixesyou"
		value="<?php echo esc_attr( $data['pledge-contributors'] ); ?>"
		<?php echo esc_attr( $required ); ?>
		aria-describedby="5ftf-pledge-contributors-help"
	/>
	<p id="5ftf-pledge-contributors-help">
		<?php esc_html_e( 'Separate each WordPress.org username with a comma.', 'wordpressorg' ); ?>
	</p>
</div>
