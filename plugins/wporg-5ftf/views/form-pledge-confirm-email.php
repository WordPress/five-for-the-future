<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

/**
 * @var bool   $email_confirmed
 * @var string $directory_url
 */

?>

<?php if ( true === $email_confirmed ) : ?>

	<div class="notice notice-success notice-alt">
		<p>
			Thank you for confirming your address! Your pledge will show up in <a href="<?php echo esc_url( $directory_url ); ?>">the directory</a> once one of your contributors confirms their participation.
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

		<p>
			<button class="button-secondary">
				<?php esc_html_e( 'Resend confirmation email', 'wporg' ); ?>
				<?php // todo make ^ work when making the other 2 work ?>
			</button>
		</p>
	</div>

<?php endif; ?>
