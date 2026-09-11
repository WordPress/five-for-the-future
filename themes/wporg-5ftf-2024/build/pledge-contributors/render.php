<?php

use WordPressDotOrg\FiveForTheFuture\Contributor;
use const WordPressdotorg\Theme\FiveForTheFuture_2024\Pledge_Contributors\TRUNCATED_MAX;

if ( ! $block->context['postId'] ) {
	return '';
}

$contributors = Contributor\get_contributor_user_objects(
	Contributor\get_pledge_contributors( $block->context['postId'], 'publish' )
);

$is_truncated = isset( $attributes['className'] ) && str_contains( $attributes['className'], 'is-style-truncated' );

// Initialize count to zero for untruncated view.
$count_more = 0;

// Set avatar size at smallest usage in px.
$avatar_size = 70;

if ( $is_truncated ) {
	$count_more   = count( $contributors ) - TRUNCATED_MAX;
	$contributors = array_splice( $contributors, 0, TRUNCATED_MAX );
	$avatar_size  = 30;
}

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
>
	<?php if ( ! empty( $contributors ) ) : ?>
		<ul class="pledge-contributors">
			<?php foreach ( $contributors as $contributor ) : ?>
				<li class="pledge-contributor">
					<span class="pledge-contributor__avatar">
						<a href="<?php echo esc_url( 'https://profiles.wordpress.org/' . $contributor->user_nicename . '/' ); ?>">
							<?php echo get_avatar( $contributor->user_email, $avatar_size, 'mystery', $contributor->display_name ); ?>
						</a>
					</span>
				</li>
			<?php endforeach; ?>
			<?php if ( $count_more > 0 ) : ?>
				<li class="pledge-contributors__more"><?php echo '+' . esc_html( $count_more ); ?></li>
			<?php endif; ?>
		</ul>
	<?php else : ?>
		<p class="pledge-no-contributors"><?php esc_html_e( 'No confirmed contributors yet.', 'wporg-5ftf' ); ?></p>
	<?php endif; ?>
</div>
