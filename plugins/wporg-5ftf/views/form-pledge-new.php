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

	<?php foreach ( $messages as $message ) : ?>
		<div class="notice notice-error">
			<?php echo wpautop( $message ); ?>
		</div>
	<?php endforeach; ?>

<?php endif; ?>

<?php if ( true === $complete ) : ?>

	<div class="notice notice-info">
		<?php echo wpautop( __( 'Thank you for your submission. You will receive an email confirmation.', 'wporg' ) ); ?>
	</div>

<?php else : ?>

	<form id="5ftf-form-pledge-new" action="" method="post">
		<?php
		require get_views_path() . 'inputs-pledge-org-info.php';
		require get_views_path() . 'inputs-pledge-org-logo.php';
		require get_views_path() . 'inputs-pledge-org-email.php';
		require get_views_path() . 'inputs-pledge-contributors.php';
		require get_views_path() . 'inputs-pledge-new-misc.php';
		?>

		<div>
			<input type="submit" id="5ftf-pledge-submit" name="action" class="button button-primary" value="<?php esc_attr_e( 'Submit Pledge', 'wporg' ); ?>" />
		</div>
	</form>

<?php endif; ?>
