<?php
/** @var bool $editable */
/** @var array $data */
?>

<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="5ftf-org-name">
					<?php esc_html_e( 'Organization Name', 'wordpressorg' ); ?>
				</label>
			</th>
			<td>
				<input
					type="text"
					class="large-text"
					id="5ftf-org-name"
					name="org-name"
					value="<?php echo esc_attr( $data['org-name'] ); ?>"
					required
					<?php echo ( $editable ) ? '' : 'readonly'; ?>
				/>
			</td>
		</tr>
		<tr>
			<th>
				<label for="5ftf-org-url">
					<?php esc_html_e( 'Website Address', 'wordpressorg' ); ?>
				</label>
			</th>
			<td>
				<input
					type="url"
					class="large-text"
					id="5ftf-org-url"
					name="org-url"
					value="<?php echo esc_attr( $data['org-url'] ); ?>"
					required
					<?php echo ( $editable ) ? '' : 'readonly'; ?>
				/>
			</td>
		</tr>
		<tr>
			<th>
				<label for="5ftf-org-description">
					<?php _e( 'Organization Blurb', 'wordpressorg' ); ?>
				</label>
			</th>
			<td>
				<textarea
					class="large-text"
					id="5ftf-org-description"
					name="org-description"
					required
					<?php echo ( $editable ) ? '' : 'readonly'; ?>
				>
					<?php echo esc_html( $data['org-description'] ); ?>
				</textarea>
			</td>
		</tr>
	</tbody>
</table>
