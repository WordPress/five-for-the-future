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
						<?php echo esc_html( date( 'Y-m-d G:i:s', $entry['timestamp'] ) ); ?>
					</td>
					<td>
						<details>
							<summary>
								<?php echo wp_kses_data( $entry['message'] ); ?>
							</summary>
							<?php if ( ! empty( $entry['data'] ) ) : ?>
								<pre><?php echo esc_html( print_r( $entry['data'], true ) ); ?></pre>
							<?php endif; ?>
						</details>
					</td>
					<td>
						<?php
						$user = get_user_by( 'id', $entry['user_id'] );
						if ( $user ) : ?>
							<?php echo sanitize_user( $user->user_login ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

	<?php else : ?>

		<p>
			There are no log entries.
		</p>

	<?php endif; ?>
</div>
