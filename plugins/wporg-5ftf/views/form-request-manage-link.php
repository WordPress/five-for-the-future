<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use WordPressDotOrg\FiveForTheFuture\Pledge;

defined( 'WPINC' ) || die();

$pledge_id = ( Pledge\CPT_ID === get_post_type() ) ? get_post()->ID : absint( $_REQUEST['pledge_id'] ?? 0 );
?>

<form method="post" class="pledge-email-form">
	<input type="hidden" name="pledge_id" value="<?php echo esc_attr( $pledge_id ); ?>" />

	<label for="pledge_admin_address">
		<?php esc_html_e( 'Email Address', 'wporg-5ftf' ); ?>
	</label>

	<input
		id="pledge_admin_address"
		name="pledge_admin_address"
		type="email"
		required
		value=""
	/>

	<div class="message"></div>

	<input
		type="submit"
		class="button"
		name="get_manage_pledge_link"
		value="<?php esc_attr_e( 'Submit', 'wporg-5ftf' ); ?>"
	/>
</form>
