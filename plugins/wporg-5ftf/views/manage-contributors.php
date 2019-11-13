<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

use function WordPressDotOrg\FiveForTheFuture\get_views_path;

/** @var array $contributors */
/** @var array $data */
/** @var bool  $readonly */
?>

<script type="text/template" id="tmpl-5ftf-contributor-lists">
	<# if ( data.publish.length ) { #>
		<h3 class="contributor-list-heading"><?php esc_html_e( 'Confirmed', 'wporg' ); ?></h3>
		<ul class="contributor-list publish">{{{ data.publish }}}</ul>
	<# } #>
	<# if ( data.pending.length ) { #>
		<h3 class="contributor-list-heading"><?php esc_html_e( 'Unconfirmed', 'wporg' ); ?></h3>
		<ul class="contributor-list pending">{{{ data.pending }}}</ul>
	<# } #>
</script>

<script type="text/template" id="tmpl-5ftf-contributor">
	<li>
		<button
			class="button-link button-link-delete"
			data-action="remove-contributor"
			data-pledge-post="{{ data.pledgeId }}"
			data-contributor-post="{{ data.contributorId }}"
			data-confirm="{{ data.removeConfirm }}"
			aria-label="{{ data.removeLabel }}"
		>
			<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
		</button>
		{{{ data.avatar }}}
		<span class="contributor-list__name">
			{{ data.name }}
		</span>
		<# if ( 'pending' === data.status ) { #>
			<button
				class="button"
				data-action="resend-contributor-confirmation"
				data-pledge-post="{{ data.pledgeId }}"
				data-contributor-post="{{ data.contributorId }}"
			>
				{{ data.resendLabel }}
			</button>
		<# } #>
	</li>
</script> 

<div id="5ftf-contributors">
	<div class="pledge-contributors">
		<?php if ( ! empty( $contributors ) ) : ?>
			<?php
			printf(
				'<script>var fftfContributors = JSON.parse( decodeURIComponent( \'%s\' ) );</script>',
				rawurlencode( wp_json_encode( $contributors ) )
			);
			?>
		<?php else : ?>
			<p><?php esc_html_e( 'There are no contributors added to this pledge yet.', 'wporg' ); ?></p>
		<?php endif; ?>
	</div>

	<hr />

	<?php
	$data = [ 'pledge-contributors' => '' ];
	require get_views_path() . 'inputs-pledge-contributors.php';
	?>

	<div id="add-contrib-message"></div>

	<button
		class="button-primary"
		data-action="add-contributor"
		data-pledge-post="<?php the_ID(); ?>"
	>
		<?php esc_html_e( 'Add new contributor', 'wporg' ); ?>
	</button>
</div>
