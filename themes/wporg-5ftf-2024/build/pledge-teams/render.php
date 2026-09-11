<?php

use WordPressDotOrg\FiveForTheFuture\XProfile;

if ( ! $block->context['postId'] ) {
	return '';
}

$contribution_data = XProfile\get_aggregate_contributor_data_for_pledge( $block->context['postId'] );

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
>
	<ul class="team-grid">
		<?php foreach ( $contribution_data['teams'] as $team ) :
			$badge_classes = XProfile\get_association_classes( $team );
			?>
			<li>
				<div class="badge item dashicons <?php echo esc_attr( implode( ' ', $badge_classes ) ); ?>"></div>
				<span class="badge-label"><?php echo esc_html( $team ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="has-small-font-size"><a href="https://make.wordpress.org/"><?php esc_html_e( 'Learn more about contributor teams', 'wporg-5ftf' ); ?></a></p>
</div>
