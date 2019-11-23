<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

use function WordPressDotOrg\FiveForTheFuture\get_views_path;
use WP_Post;

/**
 * @var array        $messages
 * @var bool         $complete
 * @var string       $directory_url
 */

require __DIR__ . '/partial-result-messages.php';

?>

<!-- TODO Reveal this once managing an existing pledge is actually possible.
<p>
	<a href="#">Manage an existing pledge</a>
</p>
-->

<?php if ( true === $complete ) : ?>

	<div id="form-messages" class="notice notice-success notice-alt">
		<p>
			<?php esc_html_e( "Thanks for pledging to Five for the Future! Your new pledge profile has been created, and we've emailed you a link to confirm your address. Once that's done, we'll also email confirmation links to the contributors you named in your pledge.", 'wporg-5ftf' ); ?>
		</p>

		<p>
			<?php echo wp_kses_post( sprintf(
				__( "After those steps are completed (and at least one contributor confirms), your pledge will appear in <a href=\"%s\">the directory</a>. Once each contributor has confirmed, they'll appear on your pledge as well.", 'wporg-5ftf' ),
				esc_url( $directory_url )
			) ); ?>
		</p>

		<p>
			<?php echo wp_kses_post(
				sprintf(
					__( 'Do you want to hire additional employees to contribute to WordPress? <a href="%s">Consider posting a job listing on jobs.wordpress.net</a>.', 'wporg-5ftf' ),
					'https://jobs.wordpress.net'
				)
			); ?>
		</p>
	</div>

<?php else : ?>

	<form class="pledge-form" id="5ftf-form-pledge-new" action="#form-messages" method="post" enctype="multipart/form-data">
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
				value="<?php esc_attr_e( 'Submit Pledge', 'wporg-5ftf' ); ?>"
			/>
		</div>
	</form>

<?php endif; ?>
