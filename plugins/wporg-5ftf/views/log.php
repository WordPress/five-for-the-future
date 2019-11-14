<?php
namespace WordPressDotOrg\FiveForTheFuture\View;

/** @var array $log */
?>

<div class="5ftf-activity-log">
	<?php if ( ! empty( $log ) ) : ?>

		<table class="striped widefat">
			<thead>
			<tr>
				<td>Date</td>
				<td>Entry</td>
				<td>User</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $log as $entry ) : ?>
				<tr>
					<td>
						<?php echo esc_html( date( 'Y-m-d H:i:s', $entry['timestamp'] ) ); ?>
					</td>
					<td>
						<?php if ( ! empty( $entry['data'] ) ) : ?>
							<details>
								<summary>
									<?php echo wp_kses_data( $entry['message'] ); ?>
								</summary>
								<pre><?php echo esc_html( print_r( $entry['data'], true ) ); ?></pre>
							</details>
						<?php else : ?>
							<?php echo wp_kses_data( $entry['message'] ); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php
						$user = get_user_by( 'id', $entry['user_id'] );

						if ( $user ) {
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitize_user prevents unsafe characters.
							echo sanitize_user( $user->user_login );
						} elseif ( ! empty( $entry['user_id'] ) ) {
							echo esc_html( $entry['user_id'] );
						}

						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

	<?php else : ?>

		<p>There are no log entries.</p>

	<?php endif; ?>
</div>
