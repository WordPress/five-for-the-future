<?php
/**
 *
 */

/** @var array $messages */
/** @var bool  $complete */
?>

<p>
	<a href="#">Manage an existing pledge</a>
</p>

<?php if ( ! empty( $messages ) ) : ?>

	<?php foreach ( $messages as $message ) : ?>
		<div class="notice notice-error">
			<?php echo wpautop( $message ); ?>
		</div>
	<?php endforeach; ?>

<?php endif; ?>

<?php if ( true === $complete ) : ?>

	<div class="notice notice-info">
		<?php echo wpautop( __( 'Thank you for your submission. You will receive an email confirmation.', 'wporg' ) ); ?>
	</div>

<?php else : ?>

	<form action="" method="post">
		<div>
			<label for="5ftf-org-name">
				<?php _e( 'Organization Name', 'wporg' ); ?>
			</label>
			<input
				type="text"
				id="5ftf-org-name"
				name="org-name"
				value="<?php echo esc_attr( filter_input( INPUT_POST, 'org-name' ) ); ?>"
				required
			/>
		</div>

		<div>
			Logo <strong>TODO</strong>
		</div>

		<div>
			<label for="5ftf-org-description">
				<?php _e( 'Organization Blurb', 'wporg' ); ?>
			</label>
			<textarea
				id="5ftf-org-description"
				name="org-description"
				required
			>
				<?php echo esc_html( filter_input( INPUT_POST, 'org-description' ) ); ?>
			</textarea>
			<span class="field-help">280 characters</span>
		</div>

		<div>
			<label for="5ftf-admin-wporg-username">
				<?php _e( 'Admin Username', 'wporg' ); ?>
			</label>
			<input
				type="text"
				id="5ftf-admin-wporg-username"
				name="admin-wporg-username"
				value="<?php echo esc_attr( filter_input( INPUT_POST, 'admin-wporg-username' ) ); ?>"
				required
			/>
			<span class="field-help">This user will be responsible for managing your organization's pledge.</span>
		</div>

		<div>
			<label for="5ftf-contributor-wporg-usernames">
				<?php _e( 'Contributing Employee Usernames', 'wporg' ); ?>
			</label>
			<input
				type="text"
				id="5ftf-contributor-wporg-usernames"
				name="contributor-wporg-usernames"
				value="<?php echo esc_attr( filter_input( INPUT_POST, 'contributor-wporg-usernames' ) ); ?>"
				required
			/>
			<span class="field-help">Separate each username with a comma.</span>
		</div>

		<div>
			<label for="5ftf-pledge-agreement">
				<input
					type="checkbox"
					id="5ftf-pledge-agreement"
					name="pledge-agreement"
					required
				/>
				<?php _e( 'I agree', 'wporg' ); ?>
			</label>
		</div>

		<div>
			<input type="submit" id="5ftf-pledge-submit" name="action" class="button button-primary" value="<?php esc_attr_e( 'Submit Pledge', 'wporg' ); ?>" />
		</div>
	</form>

<?php endif; ?>
