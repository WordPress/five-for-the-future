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
		<table class="contributor-list publish striped widefat">
			<thead>
				<th scope="col"><?php esc_html_e( 'Contributor', 'wporg-5ftf' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Date Confirmed', 'wporg-5ftf' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Remove Contributor', 'wporg-5ftf' ); ?></th>
			</thead>
			<tbody>{{{ data.publish }}}</tbody>
		</table>
	<# } #>
	<# if ( data.pending.length ) { #>
		<h3 class="contributor-list-heading"><?php esc_html_e( 'Unconfirmed', 'wporg' ); ?></h3>
		<table class="contributor-list pending striped widefat">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Contributor', 'wporg-5ftf' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Resend Confirmation', 'wporg-5ftf' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Remove Contributor', 'wporg-5ftf' ); ?></th>
				</tr>
			</thead>
			<tbody>{{{ data.pending }}}</tbody>
		</table>
	<# } #>
	<# if ( ! data.publish.length && ! data.pending.length ) { #>
		<p><?php esc_html_e( 'There are no contributors added to this pledge yet.', 'wporg-5ftf' ); ?></p>
	<# } #>
</script>

<script type="text/template" id="tmpl-5ftf-contributor">
	<tr>
		<th scope="row">
			{{{ data.avatar }}}
			<span class="contributor-list__name">
				{{ data.displayName }} ({{ data.name }})
			</span>
		</th>
		<# if ( 'pending' === data.status ) { #>
			<td>
				<button
					class="button"
					data-action="resend-contributor-confirmation"
					data-contributor-post="{{ data.contributorId }}"
				>
					{{ data.resendLabel }}
				</button>
			</td>
		<# } else { #>
			<td>{{ data.publishDate }}</td>
		<# } #>
		<td>
			<button
				class="button-link button-link-delete"
				data-action="remove-contributor"
				data-contributor-post="{{ data.contributorId }}"
				data-confirm="{{ data.removeConfirm }}"
				aria-label="{{ data.removeLabel }}"
			>
				<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				<?php esc_html_e( 'Remove', 'wporg-5ftf' ); ?>
			</button>
		</td>
	</tr>
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

	<?php
	$data = [ 'pledge-contributors' => '' ];
	require get_views_path() . 'inputs-pledge-contributors.php';
	?>

	<div id="add-contrib-message"></div>

	<button
		class="button-primary"
		data-action="add-contributor"
	>
		<?php esc_html_e( 'Add new contributors', 'wporg' ); ?>
	</button>
</div>
