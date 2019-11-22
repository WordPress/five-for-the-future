<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

/**
 * @var array  $data
 * @var int    $pledge_id
 * @var bool   $readonly
 */

?>

<div class="form-field">
	<label for="5ftf-org-name">
		<?php esc_html_e( 'Organization Name', 'wporg-5ftf' ); ?>
	</label>
	<input
		type="text"
		id="5ftf-org-name"
		name="org-name"
		value="<?php echo esc_attr( $data['org-name'] ); ?>"
		required
		<?php echo $readonly ? 'readonly' : ''; ?>
	/>
</div>

<?php if ( ! is_admin() ) : ?>
	<?php if ( has_post_thumbnail( $pledge_id ) ) : ?>
		<div class="form-field form-field__logo-display">
			<?php echo get_the_post_thumbnail( $pledge_id, 'pledge-logo' ); ?>
		</div>
	<?php endif; ?>
	<div class="form-field form-field__logo">
		<label for="5ftf-org-logo">
			<?php esc_html_e( 'Logo', 'wporg-5ftf' ); ?>
		</label>
		<br />
		<input
			type="file"
			id="5ftf-org-logo"
			name="org-logo"
		/>
	</div>
<?php endif; ?>

<div class="form-field">
	<label for="5ftf-org-url">
		<?php esc_html_e( 'Website Address', 'wporg-5ftf' ); ?>
	</label>
	<input
		type="url"
		id="5ftf-org-url"
		name="org-url"
		placeholder="https://example.com"
		value="<?php echo esc_attr( $data['org-url'] ); ?>"
		required
		<?php echo $readonly ? 'readonly' : ''; ?>
	/>
</div>

<div class="form-field">
	<label for="5ftf-org-description">
		<?php esc_html_e( 'Organization Blurb', 'wporg-5ftf' ); ?>
	</label>
	<textarea
		id="5ftf-org-description"
		name="org-description"
		rows="5"
		required
		<?php echo $readonly ? 'readonly' : ''; ?>
	><?php /* phpcs:ignore -- php tags should be on the same line as textarea to prevent extra whitespace */
		echo esc_html( str_replace( [ '<p>', '</p>', '<br />' ], '', $data['org-description'] ) );
	/* phpcs:ignore */ ?></textarea>
</div>
