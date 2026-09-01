<?php
/**
 * Tests for pledge meta accessors.
 */

declare( strict_types = 1 );

use WordPressDotOrg\FiveForTheFuture\{ Pledge, PledgeMeta };

defined( 'WPINC' ) || die();

/**
 * @group pledge-meta
 */
class Test_Pledge_Meta extends WP_UnitTestCase {
	/**
	 * Create a published pledge with a known stored description.
	 *
	 * @param string $description Value to store for the `org-description` meta key.
	 *
	 * @return int The new pledge's post ID.
	 */
	protected function create_pledge( string $description = 'StoredCanary' ): int {
		$pledge_id = self::factory()->post->create( array(
			'post_type'   => Pledge\CPT_ID,
			'post_status' => 'publish',
		) );

		update_post_meta( $pledge_id, PledgeMeta\META_PREFIX . 'org-description', $description );

		return $pledge_id;
	}

	/**
	 * A submission value overriding the stored meta must be run through the field's sanitize callback, so a
	 * reflected `<script>` payload can never be emitted verbatim.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeMeta\get_pledge_meta
	 */
	public function test_submission_overlay_strips_script_tags(): void {
		$pledge_id = $this->create_pledge();

		$meta = PledgeMeta\get_pledge_meta(
			$pledge_id,
			'',
			array( 'org-description' => '<script>alert(document.domain)</script>zzCANARY' )
		);

		$this->assertStringNotContainsString( '<script', $meta['org-description'], 'Script tags must be stripped from the overlaid submission.' );
		$this->assertStringContainsString( 'zzCANARY', $meta['org-description'], 'The overlaid submission should still be reflected, only sanitized.' );
	}

	/**
	 * The sanitize callback also removes markup carrying event handlers, so `<img onerror>` cannot execute.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeMeta\get_pledge_meta
	 */
	public function test_submission_overlay_strips_event_handlers(): void {
		$pledge_id = $this->create_pledge();

		$meta = PledgeMeta\get_pledge_meta(
			$pledge_id,
			'',
			array( 'org-description' => '<img src=x onerror=alert(1)>zzCANARY' )
		);

		$this->assertStringNotContainsString( 'onerror', $meta['org-description'], 'Event-handler attributes must not survive.' );
		$this->assertStringContainsString( 'zzCANARY', $meta['org-description'] );
	}

	/**
	 * Readers pass no submission, so the accessor returns the stored meta untouched. Only a caller that opts in
	 * by passing a submission gets the in-progress input overlaid.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeMeta\get_pledge_meta
	 */
	public function test_reader_returns_stored_value(): void {
		$pledge_id = $this->create_pledge( 'StoredCanary' );

		$reader  = PledgeMeta\get_pledge_meta( $pledge_id );
		$overlay = PledgeMeta\get_pledge_meta( $pledge_id, '', array( 'org-description' => 'SubmittedCanary' ) );

		$this->assertSame( 'StoredCanary', $reader['org-description'], 'A reader must return the stored value.' );
		$this->assertSame( 'SubmittedCanary', $overlay['org-description'], 'An explicit submission must override the stored value.' );
	}

	/**
	 * With no pledge and no submission, every key falls back to a default rather than raising an undefined-index
	 * warning for the config's absent `default` entry.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeMeta\get_pledge_meta
	 */
	public function test_no_pledge_returns_defaults(): void {
		$meta = PledgeMeta\get_pledge_meta();

		$this->assertSame( '', $meta['org-description'] );
		$this->assertSame( '', $meta['pledge-email-confirmed'] );
	}

	/**
	 * The "no POST body" sentinel discards the whole submission, so a form re-render with no real input falls
	 * back to the stored meta.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeMeta\get_pledge_meta
	 */
	public function test_empty_post_sentinel_falls_back_to_stored_value(): void {
		$pledge_id = $this->create_pledge( 'StoredCanary' );

		$meta = PledgeMeta\get_pledge_meta(
			$pledge_id,
			'',
			array(
				'empty_post'      => true,
				'org-description' => '<script>alert(1)</script>InjectedCanary',
			)
		);

		$this->assertSame( 'StoredCanary', $meta['org-description'], 'The empty_post sentinel must discard the submission.' );
	}
}
