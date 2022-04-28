<?php

namespace WordPressDotOrg\FiveForTheFuture\View;
use const WordPressDotOrg\FiveForTheFuture\Pledge\CPT_ID;
use function WordPressDotOrg\FiveForTheFuture\get_views_path;

defined( 'WPINC' ) || die();

$pledge_id   = ( CPT_ID === get_post_type() ) ? get_post()->ID : absint( $_REQUEST['pledge_id'] ?? 0 );
$pledge_name = get_the_title( $pledge_id );
?>

<div id="send-link-dialog">
	<?php require get_views_path() . 'partial-result-messages.php'; ?>

	<p>
		<?php echo esc_html( sprintf(
			__( "If you're an admin for %s, you can request a new edit link using this form. Enter your email address and we'll send you a new link.", 'wporg-5ftf' ),
			$pledge_name
		) ); ?>
	</p>

	<?php require get_views_path() . 'form-request-manage-link.php'; ?>
</div>
