<?php

namespace WordPressDotOrg\FiveForTheFuture\View;

defined( 'WPINC' ) || die();

/**
 * @var array $messages
 * @var array $errors
 */

?>

<div id="form-messages">
	<?php if ( ! empty( $messages ) ) : ?>
		<div id="success-messages" class="notice notice-success notice-alt">
			<?php foreach ( $messages as $message ) : ?>
				<p>
					<?php echo wp_kses_post( $message ); ?>
				</p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $errors ) ) : ?>
		<div id="error-messages" class="notice notice-error notice-alt">
			<?php foreach ( $errors as $error ) : ?>
				<p>
					<?php echo wp_kses_post( $error ); ?>
				</p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
