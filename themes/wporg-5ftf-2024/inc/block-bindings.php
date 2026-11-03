<?php
/**
 * Set up custom block bindings.
 */

namespace WordPressdotorg\Theme\FiveForTheFuture_2024\Block_Bindings;

use WordPressDotOrg\FiveForTheFuture\XProfile;
use function WordPressDotOrg\FiveForTheFuture\PledgeMeta\get_pledge_meta;
use const WordPressDotOrg\FiveForTheFuture\Contributor\CPT_ID as CONTRIBUTOR_POST_TYPE;

add_action( 'init', __NAMESPACE__ . '\register_block_bindings' );

/**
 * Register block bindings.
 *
 * This registers some sources which can be used to dynamically inject content
 * into block text or attributes.
 */
function register_block_bindings() {
	register_block_bindings_source(
		'wporg-5ftf/pledge-meta',
		array(
			'label'              => 'Pledge meta',
			'uses_context'       => [ 'postId' ],
			'get_value_callback' => __NAMESPACE__ . '\get_meta_binding_value',
		)
	);
}

/**
 * Callback to provide the binding value.
 */
function get_meta_binding_value( $args, $block ) {
	if ( ! isset( $args['key'] ) ) {
		return '';
	}

	$data = get_pledge_meta( $block->context['postId'] );
	if ( empty( $data ) ) {
		return '';
	}

	switch ( $args['key'] ) {
		case 'org-url-link':
			return sprintf(
				'<a href="%s" rel="nofollow">%s</a>',
				esc_url( $data['org-url'] ),
				esc_html( $data['org-domain'] )
			);
		case 'org-contribution-details':
			$contribution_data = XProfile\get_aggregate_contributor_data_for_pledge( $block->context['postId'] );
			return sprintf(
				__( '%1$s sponsors %2$s for a total of <strong>%3$s&nbsp;hours</strong> per week across <strong>%4$d&nbsp;teams</strong>.', 'wporg-5ftf' ),
				get_the_title( $block->context['postId'] ),
				sprintf(
					_n( '<strong>%d&nbsp;contributor</strong>', '<strong>%d&nbsp;contributors</strong>', $contribution_data['contributors'], 'wporg-5ftf' ),
					number_format_i18n( absint( $contribution_data['contributors'] ) )
				),
				number_format_i18n( absint( $contribution_data['hours'] ) ),
				count( $contribution_data['teams'] )
			);
		case 'org-contribution-short-details':
			$contribution_data = XProfile\get_aggregate_contributor_data_for_pledge( $block->context['postId'] );
			return sprintf(
				/* translators: 1: Number of contributors. 2: Number of hours. */
				_n(
					'sponsors %1$s contributor for %2$s hours per week.',
					'sponsors %1$s contributors for %2$s hours per week.',
					absint( $contribution_data['contributors'] ),
					'wporg-5ftf'
				),
				number_format_i18n( absint( $contribution_data['contributors'] ) ),
				number_format_i18n( absint( $contribution_data['hours'] ) )
			);
		case 'user-contribution-details':
			if ( ! is_user_logged_in() ) {
				return '';
			}

			$user = wp_get_current_user();
			$profile_data = XProfile\get_contributor_user_data( $user->ID );

			$contributor_publish_query = new \WP_Query( array(
				'title'          => $user->user_login,
				'post_type'      => CONTRIBUTOR_POST_TYPE,
				'post_status'    => array( 'publish' ),
				'posts_per_page' => 100,
				'fields'         => 'ids',
			) );
			$pledge_count              = $contributor_publish_query->found_posts;
			return wp_kses_data( sprintf(
				/* translators: %1$s is the number of hours, %2$s is the number of organizations, and %3$s is an edit link. */
				_n(
					'You pledge <strong>%1$s hours a week</strong> %3$s across %2$s organization.',
					'You pledge <strong>%1$s hours a week</strong> %3$s across %2$s organizations.',
					$pledge_count,
					'wporg-5ftf'
				),
				$profile_data['hours_per_week'],
				$pledge_count,
				sprintf(
					'<a aria-label="%1$s" href="https://profiles.wordpress.org/me/profile/edit/group/5/">%2$s</a>',
					__( 'edit hours pledged', 'wporg-5ftf' ),
					__( '(edit)', 'wporg-5ftf' )
				)
			) );
	}
}
