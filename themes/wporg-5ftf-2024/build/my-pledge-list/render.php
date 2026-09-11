<?php
/**
 * Render the pledge list for the current user.
 * Mostly copied from Contributor\render_my_pledges, updated markup to use blocks.
 */

use WordPressDotOrg\FiveForTheFuture\{ Contributor, XProfile };
use function WordPressdotorg\Theme\FiveForTheFuture_2024\My_Pledge_List\{render_single_pledge, render_notice};
use const WordPressDotOrg\FiveForTheFuture\Contributor\CPT_ID as CONTRIBUTOR_POST_TYPE;

if ( ! is_user_logged_in() ) {
	render_notice( 'warning', sprintf(
		__( 'Please <a href="%s">log in to your WordPress.org account</a> in order to view your pledges.', 'wporg-5ftf' ),
		esc_url( wp_login_url( get_permalink() ) )
	) );
	return;
}

$user            = wp_get_current_user();
$profile_data    = XProfile\get_contributor_user_data( $user->ID );
$pledge_url      = get_permalink( get_page_by_path( 'for-organizations' ) );
$success_message = Contributor\process_my_pledges_form();

$contributor_pending_posts = get_posts( array(
	'title'       => $user->user_login,
	'post_type'   => CONTRIBUTOR_POST_TYPE,
	'post_status' => array( 'pending' ),
	'numberposts' => 100,
) );

$contributor_publish_posts = get_posts( array(
	'title'       => $user->user_login,
	'post_type'   => CONTRIBUTOR_POST_TYPE,
	'post_status' => array( 'publish' ),
	'numberposts' => 100,
) );

$has_contributions = $contributor_pending_posts || $contributor_publish_posts;
$has_profile_data  = $profile_data['hours_per_week'] && $profile_data['team_names'];

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
>
	<?php
	if ( $success_message ) {
		render_notice( 'success', $success_message );
		echo '<div style="margin-top:var(--wp--preset--spacing--40);height:0" aria-hidden="true" class="wp-block-spacer"></div>';
	}
	?>

	<?php if ( $has_contributions ) : ?>

		<?php if ( $contributor_publish_posts ) : ?>
			<h2 class="screen-reader-text"><?php esc_html_e( 'Pledges', 'wporg-5ftf' ); ?></h2>

			<div class="my-pledges__list">
				<?php
				foreach ( $contributor_publish_posts as $contributor_post ) {
					render_single_pledge( $contributor_post, $has_profile_data );
				}
				?>
			</div>

		<?php endif; ?>

		<?php if ( $contributor_pending_posts ) : ?>

			<h2><?php esc_html_e( 'Pending Pledges', 'wporg-5ftf' ); ?></h2>

			<div class="my-pledges__list is-pending-list">
				<?php
				if ( ! $has_profile_data ) {
					render_notice(
						'warning',
						sprintf(
							__( 'You need to <a href="%s">update your profile</a> before joining an organization.', 'wporg-5ftf' ),
							'https://profiles.wordpress.org/me/profile/edit/group/5/'
						)
					);
				}

				foreach ( $contributor_pending_posts as $contributor_post ) {
					render_single_pledge( $contributor_post, $has_profile_data );
				}
				?>
			</div>

		<?php endif; ?>

	<?php else : ?>

		<p>
			<?php
			echo wp_kses_data( sprintf(
				__( 'You don’t currently have any sponsorships. If your employer is sponsoring part of your time to contribute to WordPress, please ask them to <a href="%s">submit a pledge</a> and list you as a contributor.', 'wporg-5ftf' ),
				esc_url( $pledge_url )
			) );
			?>
		</p>

	<?php endif; ?>
</div>
