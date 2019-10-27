<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

/**
 * @var bool   $email_confirmed
 * @var string $directory_url
 * @var int    $pledge_id
 */

?>

<?php if ( true === $email_confirmed ) : ?>

	<div class="notice notice-success notice-alt">
		<p>
			Thank you for confirming your address! We've emailed confirmation links to your contributors, and your pledge will show up in <a href="<?php echo esc_url( $directory_url ); ?>">the directory</a> once one of them confirms their participation.
		</p>
	</div>

<?php else : ?>

	<div class="notice notice-error notice-alt">
		<p>
			<?php
			/*
			 * There could be other reasons it failed, like an invalid token, but this is the most common reason,
			 * and the only one that normal users should experience, so we're assuming it in order to provide
			 * the best UX.
			 */
			?>
			Your confirmation link has expired, please obtain a new one:
		</p>

		<form action="" method="get">
			<input type="hidden" name="pledge_id" value="<?php echo esc_attr( $pledge_id ); ?>" />

			<p>
				<input
					type="submit"
					name="resend_pledge_confirmation"
					value="Resend Confirmation"
				/>
			</p>
		</form>
	</div>

<?php endif; ?>
