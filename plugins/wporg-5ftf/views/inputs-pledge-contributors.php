<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $contributors */
/** @var array $data */
/** @var bool  $readonly */
?>

<?php if ( empty( $contributors ) ) : ?>

<div class="form-field">
	<label for="5ftf-pledge-contributors">
		<?php esc_html_e( 'Contributors', 'wordpressorg' ); ?>
	</label>
	<input
		type="text"
		id="5ftf-pledge-contributors"
		name="pledge-contributors"
		value="<?php echo esc_attr( $data['pledge-contributors'] ); ?>"
		required
		aria-describedby="5ftf-pledge-contributors-help"
	/>
	<p id="5ftf-pledge-contributors-help">
		<?php esc_html_e( 'Separate each username with a comma.', 'wordpressorg' ); ?>
	</p>
</div>

<?php else : ?>

<div class="5ftf-contributors">
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
					$contributor = get_user_by( 'login', $contributor_post->post_title );
					?>
					<li>
						<?php echo get_avatar( $contributor->user_email, 32 ); ?>
						<?php echo esc_html( $contributor_post->post_title ); ?>
						<!-- TODO These buttons don't do anything yet.
						<button class="button-primary" data-action="remove" data-contributor-post="<?php echo esc_attr( $contributor_post->ID ); ?>">
							<?php esc_html_e( 'Remove', 'wporg' ); ?>
						</button>
						<?php if ( 'pending' === $contributor_post->post_status ) : ?>
							<button class="button-secondary" data-action="resend-confirmation" data-contributor-post="<?php echo esc_attr( $contributor_post->ID ); ?>">
								<?php esc_html_e( 'Resend confirmation', 'wporg' ); ?>
							</button>
						<?php endif; ?>
						-->
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endforeach; ?>

	<!-- TODO This button doesn't do anything yet.
	<button class="button-primary" data-action="add-contributor">
		<?php esc_html_e( 'Add new contributor', 'wporg' ); ?>
	</button>
	-->
</div>

<?php endif; ?>
