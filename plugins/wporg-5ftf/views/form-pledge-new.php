<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

use function WordPressDotOrg\FiveForTheFuture\get_views_path;

/** @var array $messages */
/** @var bool  $complete */
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
		<p><?php esc_html_e( 'Thanks for pledging Five for the Future! Your new pledge profile has been created, and we’ve emailed you a link you can use to edit your pledge in the future. Your contributors have also been emailed a link to confirm their contributions with your organization.', 'wporg' ); ?></p>

		<p><?php esc_html_e( 'Once your pledge has been approved by a moderator, it will appear in the pledges list.', 'wporg' ); ?></p>

		<p><?php esc_html_e( 'Want to hire additional employees to contribute to WordPress? Post a job listing on jobs.wordpress.net.', 'wporg' ); ?></p>
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
