<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use function WordPressDotOrg\FiveForTheFuture\get_views_path;

/**
 * @var array  $messages
 * @var bool   $complete
 * @var string $directory_url
 */

?>

<p>
	<a href="#">Manage an existing pledge</a>
</p>

<?php if ( ! empty( $messages ) ) : ?>

	<div class="notice notice-error notice-alt">
	<?php foreach ( $messages as $message ) : ?>
		<p><?php echo wp_kses_post( $message ); ?></p>
	<?php endforeach; ?>
	</div>

<?php endif; ?>

<?php if ( true === $complete ) : ?>

	<div class="notice notice-success notice-alt">
		<p><?php esc_html_e( "Thanks for pledging to Five for the Future! Your new pledge profile has been created, and we’ve emailed you a link to confirm your address. Once that's done, we'll also email confirmation links to your contributors.", 'wporg' ); ?></p>

		<p>
			<?php echo wp_kses_post( sprintf(
				__( 'After those steps are completed, your pledge will appear in <a href="%s">the directory</a>.', 'wporg' ),
				esc_url( $directory_url )
			) ); ?>
		</p>

		<p>
			<?php echo wp_kses_post(
				__( 'Do you want to hire additional employees to contribute to WordPress? <a href="https://jobs.wordpress.net">Post a job listing on jobs.wordpress.net</a>.', 'wporg' )
				// todo ask mel about moving this outside the `notice-success`, since it's not really part of the success notification, and distracts from it.
				// many users have notification fatigue and no longer trust them or pay attention to them, because they're so often misused for non-critical information,
				// and the jobs thing is more of an "ad" in this context than something directly related to the process the user wants to complete
			); ?>
		</p>
	</div>

<?php else : ?>

	<form class="pledge-form" id="5ftf-form-pledge-new" action="" method="post">
		<?php
		require get_views_path() . 'inputs-pledge-org-info.php';
		require get_views_path() . 'inputs-pledge-contributors.php';
		require get_views_path() . 'inputs-pledge-org-email.php';
		require get_views_path() . 'inputs-pledge-new-misc.php';
		?>

		<div>
			<input
				type="submit"
				id="5ftf-pledge-submit"
				name="action"
				value="<?php esc_attr_e( 'Submit Pledge', 'wporg' ); ?>"
			/>
		</div>
	</form>

<?php endif; ?>
