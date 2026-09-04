<?php
/**
 * Tests for pledge meta accessors.
 */

declare( strict_types = 1 );

use WordPressDotOrg\FiveForTheFuture\{ Pledge, PledgeForm, PledgeMeta };

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

	/**
	 * Payloads that a single `strip_shortcodes()` pass leaves as a live shortcode.
	 *
	 * `[[tag]]` is core's escape syntax, which `strip_shortcode_tag()` unwraps rather
	 * than removes. The spliced ones carry no shortcode until the inner `[caption]`
	 * comes out, at which point the remainder joins into `[gallery ids="1"]`.
	 *
	 * @return array
	 */
	public function data_single_pass_survivors(): array {
		return array(
			'escaped twin'  => array( 'Org [[caption width="1" caption="x"]y[/caption]] Name' ),
			'splice'        => array( 'Org [gal[caption]lery ids="1"] Name' ),
			'double splice' => array( 'Org [ga[caption]l[caption]lery ids="1"] Name' ),
		);
	}

	/**
	 * A single-line field must not come out of sanitization carrying a shortcode.
	 *
	 * @dataProvider data_single_pass_survivors
	 *
	 * @param string $insecure Submitted value.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeMeta\sanitize_text_line
	 */
	public function test_text_line_leaves_no_shortcode( string $insecure ): void {
		$secure = PledgeMeta\sanitize_text_line( $insecure );

		$this->assertSame( array(), get_shortcode_tags_in_content( $secure ) );
		$this->assertSame( $secure, do_shortcode( $secure ) );
	}

	/**
	 * Neither must the description field, which keeps its allowed markup otherwise.
	 *
	 * @dataProvider data_single_pass_survivors
	 *
	 * @param string $insecure Submitted value.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeMeta\sanitize_description
	 */
	public function test_description_leaves_no_shortcode( string $insecure ): void {
		$secure = PledgeMeta\sanitize_description( $insecure );

		$this->assertSame( array(), get_shortcode_tags_in_content( $secure ) );
		$this->assertSame( $secure, do_shortcode( $secure ) );
	}

	/**
	 * Bracketed prose is not a shortcode, and survives sanitization unchanged.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeMeta\sanitize_text_line
	 */
	public function test_bracketed_prose_is_left_alone(): void {
		$this->assertSame(
			'A pledge from [developers] everywhere',
			PledgeMeta\sanitize_text_line( 'A pledge from [developers] everywhere' )
		);
	}

	/**
	 * Builds a submission that is valid apart from the field under test.
	 *
	 * @param string $field Submission key to set.
	 * @param string $value Value for that key.
	 *
	 * @return array
	 */
	protected function submission( string $field, string $value ): array {
		return array_merge(
			array(
				'org-name'         => 'Fixture Org',
				'org-description'  => 'A fixture pledge.',
				'org-url'          => 'https://example.org',
				'org-pledge-email' => 'fixture@example.org',
			),
			array( $field => $value )
		);
	}

	/**
	 * A submitted shortcode is refused rather than edited out of the value.
	 *
	 * @dataProvider data_single_pass_survivors
	 *
	 * @param string $insecure Submitted value.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeForm\check_invalid_submission
	 */
	public function test_submitted_shortcode_is_refused( string $insecure ): void {
		foreach ( array( 'org-name', 'org-description' ) as $field ) {
			$error = PledgeForm\check_invalid_submission( $this->submission( $field, $insecure ), 'create' );

			$this->assertWPError( $error, "A shortcode in {$field} must be refused." );
			$this->assertSame( 'shortcode_in_submission', $error->get_error_code() );
		}
	}

	/**
	 * A submission carrying only bracketed prose is not refused for it.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeForm\check_invalid_submission
	 */
	public function test_bracketed_prose_is_not_refused(): void {
		$error = PledgeForm\check_invalid_submission(
			$this->submission( 'org-name', 'A pledge from [developers] everywhere' ),
			'create'
		);

		if ( is_wp_error( $error ) ) {
			$this->assertNotSame( 'shortcode_in_submission', $error->get_error_code() );
		} else {
			$this->assertFalse( $error );
		}
	}
}
