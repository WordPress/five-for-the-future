<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

use function WordPressDotOrg\FiveForTheFuture\get_views_path;

/** @var array $messages */
/** @var bool  $updated */
?>

<form id="5ftf-form-pledge-new" action="" method="post">
	<?php
	require get_views_path() . 'inputs-pledge-org-info.php';
	require get_views_path() . 'inputs-pledge-org-logo.php';
	require get_views_path() . 'inputs-pledge-org-email.php';
	require get_views_path() . 'inputs-pledge-contributors.php';
	?>

	<div>
		<input type="submit" id="5ftf-pledge-submit" name="action" class="button button-primary" value="<?php esc_attr_e( 'Update Pledge', 'wporg' ); ?>" />
	</div>
</form>
