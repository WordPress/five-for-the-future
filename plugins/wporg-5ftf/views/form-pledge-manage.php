<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

use function WordPressDotOrg\FiveForTheFuture\get_views_path;

/** @var array $messages */
/** @var bool  $updated */
?>

<form class="pledge-form" id="5ftf-form-pledge-manage" action="" method="post">
	<?php
	require get_views_path() . 'inputs-pledge-org-info.php';
	require get_views_path() . 'inputs-pledge-contributors.php';
	require get_views_path() . 'inputs-pledge-org-email.php';
	?>

	<div>
		<input
			type="submit"
			id="5ftf-pledge-submit"
			name="action"
			value="<?php esc_attr_e( 'Update Pledge', 'wporg' ); ?>"
		/>
	</div>
</form>
