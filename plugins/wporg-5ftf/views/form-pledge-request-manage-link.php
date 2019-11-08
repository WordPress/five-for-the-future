<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

defined( 'WPINC' ) || die();

/**
 * @var array $errors
 */

// Hide it if it hasn't submitted, but show success/error messages if it was submitted.
$hidden = empty( $errors ) && empty( $_POST['get_manage_pledge_link'] ) ? 'hidden' : '';

?>

<button id="toggle-management-link-form">
	<span class="dashicons dashicons-edit"></span>
	<?php esc_html_e( 'Edit Pledge', 'wporg-5ftf' ); ?>
</button>

<div id="request-management-link" <?php echo esc_attr( $hidden ); ?> >
	<p>
		<?php esc_html_e( 'Only pledge admins can edit pledges.', 'wporg-5ftf' ); ?>
	</p>

	<p>
		<?php esc_html_e( "If you're the admin, enter your email address and a confirmation link will be sent to you.", 'wporg-5ftf' ); ?>
	</p>

	<form action="#form-messages" method="post">
		<input type="hidden" name="pledge_id" value="<?php echo esc_attr( get_post()->ID ); ?>" />

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

		<input
			type="submit"
			name="get_manage_pledge_link"
			value="Submit"
		/>

		<?php require __DIR__ . '/partial-result-messages.php'; ?>
	</form>
</div>

<script>
	( function() {
		var toggleLinkFormButton = document.getElementById( 'toggle-management-link-form' ),
			linkForm = document.getElementById( 'request-management-link' );

		// Toggle the form when the button is clicked.
		toggleLinkFormButton.addEventListener( 'click', function() {
			switch( linkForm.hidden ) {
				case true:
					linkForm.hidden = false;
					linkForm.scrollIntoView( { behavior: 'smooth' } );
					break;

				default:
					linkForm.hidden = true;
					break;
			}
		} );
	}() );
</script>
