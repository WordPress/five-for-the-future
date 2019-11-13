<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

use function WordPressDotOrg\FiveForTheFuture\get_views_path;

/** @var array $contributors */
/** @var array $data */
/** @var bool  $readonly */
?>

<div id="5ftf-contributors">
	<?php if ( ! empty( $contributors ) ) : ?>
		<?php foreach ( $contributors as $contributor_status => $group ) : ?>
			<?php if ( ! empty( $group ) ) : ?>
				<h3 class="contributor-list-heading">
					<?php
					switch ( $contributor_status ) {
						case 'pending':
							esc_html_e( 'Unconfirmed', 'wporg' );
							break;
						case 'publish':
							esc_html_e( 'Confirmed', 'wporg' );
							break;
					}
					?>
				</h3>

				<ul class="contributor-list <?php echo esc_attr( $contributor_status ); ?>">
					<?php foreach ( $group as $contributor_post ) :
						$name = $contributor_post->post_title;
						$contributor = get_user_by( 'login', $name );
						$remove_confirm = sprintf( __( 'Remove %s from this pledge?', 'wporg-5ftf' ), $name );
						$remove_label = sprintf( __( 'Remove %s', 'wporg' ), $name );
						?>
						<li>
							<button
								class="button-link button-link-delete"
								data-action="remove-contributor"
								data-pledge-post="<?php the_ID(); ?>"
								data-contributor-post="<?php echo esc_attr( $contributor_post->ID ); ?>"
								data-confirm="<?php echo esc_attr( $remove_confirm ); ?>"
								aria-label="<?php echo esc_attr( $remove_label ); ?>"
							>
								<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
							</button>
							<?php echo get_avatar( $contributor->user_email, 32 ); ?>
							<span class="contributor-list__name">
								<?php echo esc_html( $name ); ?>
							</span>
							<?php if ( 'pending' === $contributor_post->post_status ) : ?>
								<button
									class="button"
									data-action="resend-contributor-confirmation"
									data-pledge-post="<?php the_ID(); ?>"
									data-contributor-post="<?php echo esc_attr( $contributor_post->ID ); ?>"
								>
									<?php esc_html_e( 'Resend Confirmation', 'wporg' ); ?>
								</button>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'There are no contributors added to this pledge yet.', 'wporg' ); ?></p>
	<?php endif; ?>

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
