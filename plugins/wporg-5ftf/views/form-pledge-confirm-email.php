<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

use WP_Post;

/**
 * @var bool         $email_confirmed
 * @var string       $directory_url
 * @var int          $pledge_id
 * @var WP_Post|null $pledge
 */
?>

<?php if ( true === $email_confirmed ) : ?>

	<div class="notice notice-success notice-alt">
		<p>
			<?php
			printf(
				wp_kses_post( __( 'Thank you for confirming your address! We’ve emailed confirmation links to the contributors you mentioned, and your pledge will show up in <a href=\"%s\">the directory</a> once one contributor confirms their participation.', 'wporg-5ftf' ) ),
				esc_url( $directory_url )
			);
			?>
		</p>

		<?php if ( $pledge instanceof WP_Post ) : ?>
			<p>
				<?php echo wp_kses_post( sprintf(
					__( 'In the meantime, your pledge will be visible here: %s', 'wporg-5ftf' ),
					sprintf(
						'<a href="%1$s">%1$s</a>',
						esc_url( get_permalink( $pledge ) )
					)
				) ); ?>
			</p>
		<?php endif; ?>

		<p>
			<?php esc_html_e( 'Thanks again for pledging your organization’s resources to contribute to WordPress! We can do great things together!', 'wporg-5ftf' ); ?>
		</p>
	</div>

<?php else : ?>

	<div class="notice notice-error notice-alt">
		<p>
			<?php
			// There could be other reasons it failed, like an invalid token, but this is the most common reason,
			// and the only one that normal users should experience, so we're assuming it in order to provide
			// the best UX.
			esc_html_e( 'Your confirmation link has expired, please obtain a new one:', 'wporg-5ftf' );
			?>
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
