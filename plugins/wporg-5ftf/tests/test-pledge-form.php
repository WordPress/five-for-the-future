<?php
/**
 * Tests for the pledge form's logo upload.
 */

declare( strict_types = 1 );

use WordPressDotOrg\FiveForTheFuture\{ Pledge, PledgeForm };

defined( 'WPINC' ) || die();

/**
 * @group pledge-form
 */
class Test_Pledge_Form extends WP_UnitTestCase {
	/**
	 * Attachments created by a test, removed when it ends.
	 *
	 * @var int[]
	 */
	protected $attachments = array();

	/**
	 * Logo files built for a test, removed when it ends if the upload did not consume them.
	 *
	 * @var string[]
	 */
	protected $logos = array();

	/**
	 * Delete the attachments, the files the sideload wrote for them, and any unconsumed fixture.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		foreach ( $this->attachments as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}

		foreach ( $this->logos as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}

		$this->attachments = array();
		$this->logos       = array();

		parent::tear_down();
	}

	/**
	 * Write a JPEG carrying the given text in its IPTC Caption (2#120) record.
	 *
	 * @param string $caption Text to embed in the file.
	 *
	 * @return string Path to the new file.
	 */
	protected function create_logo( string $caption ): string {
		if ( ! function_exists( 'imagejpeg' ) ) {
			$this->markTestSkipped( 'The GD extension is required to build the test logo.' );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$image = imagecreatetruecolor( 40, 30 );
		$path  = get_temp_dir() . uniqid( 'logo-' ) . '.jpg';

		ob_start();
		imagejpeg( $image );
		$jpeg = ob_get_clean();
		imagedestroy( $image );

		/*
		 * An IIM record, inside an 8BIM IPTC-NAA resource, inside an APP13 segment, spliced in right after
		 * the JPEG's SOI marker. Resources are padded to an even length.
		 */
		$record   = "\x1c\x02\x78" . pack( 'n', strlen( $caption ) ) . $caption;
		$resource = "8BIM\x04\x04\x00\x00" . pack( 'N', strlen( $record ) ) . $record;
		$resource = str_pad( $resource, strlen( $resource ) + strlen( $resource ) % 2, "\x00" );
		$body     = "Photoshop 3.0\x00" . $resource;
		$segment  = "\xff\xed" . pack( 'n', strlen( $body ) + 2 ) . $body;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a fixture to the temp dir, where WP_Filesystem is not set up.
		file_put_contents( $path, substr( $jpeg, 0, 2 ) . $segment . substr( $jpeg, 2 ) );

		$this->logos[] = $path;

		$metadata = wp_read_image_metadata( $path );
		$this->assertNotEmpty( $metadata['caption'], 'The test logo must actually carry the caption.' );

		return $path;
	}

	/**
	 * Build the `$_FILES` entry the pledge form would hand to the upload.
	 *
	 * @param string $path Path to the file being submitted.
	 *
	 * @return array
	 */
	protected function submitted_file( string $path ): array {
		return array(
			'name'     => wp_basename( $path ),
			'tmp_name' => $path,
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $path ),
		);
	}

	/**
	 * Upload a logo and remember the attachment for cleanup.
	 *
	 * @param string $caption Text to embed in the file.
	 * @param string $title   Title the form would pass for the pledge.
	 *
	 * @return int The new attachment's post ID.
	 */
	protected function upload_logo( string $caption, string $title ): int {
		$attachment_id = PledgeForm\upload_image( $this->submitted_file( $this->create_logo( $caption ) ), $title );

		$this->assertNotWPError( $attachment_id );
		$this->attachments[] = $attachment_id;

		return $attachment_id;
	}

	/**
	 * Create a pledge with the given status.
	 *
	 * @param string $status Status to give the pledge.
	 *
	 * @return WP_Post
	 */
	protected function create_pledge( string $status ): WP_Post {
		return get_post(
			self::factory()->post->create( array(
				'post_type'   => Pledge\CPT_ID,
				'post_status' => $status,
			) )
		);
	}

	/**
	 * The logo's own IPTC caption must not become the attachment's content. `post_content` is stored through
	 * the post kses profile, which keeps `data-*` attributes, and the block theme renders it on the
	 * attachment's own page — so an anonymous submitter could otherwise store Interactivity directives there.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeForm\upload_image
	 */
	public function test_logo_caption_does_not_become_attachment_content(): void {
		$caption = '<div data-wp-interactive="probe" data-wp-context=\'{"u":"javascript:alert(document.domain)"}\'>zzCANARY</div>';

		/* The profile that stores `post_content` keeps these, which is what makes a stored caption dangerous. */
		$this->assertStringContainsString( 'data-wp-interactive', wp_kses_post( $caption ) );

		$attachment_id = $this->upload_logo( $caption, 'Example Org' );

		$this->assertSame( '', get_post( $attachment_id )->post_content );
	}

	/**
	 * The IPTC title channel is closed the same way: the attachment is named after the pledge, not after
	 * anything the submitted file says about itself.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeForm\upload_image
	 */
	public function test_logo_is_titled_after_the_pledge(): void {
		$this->assertSame( 'Example Org', get_post( $this->upload_logo( 'zzCANARY', 'Example Org' ) )->post_title );
	}

	/**
	 * A nameless pledge is accepted, so the file's name stands in rather than leaving the attachment untitled.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeForm\upload_image
	 */
	public function test_nameless_pledge_falls_back_to_the_file_name(): void {
		$path          = $this->create_logo( 'zzCANARY' );
		$attachment_id = PledgeForm\upload_image( $this->submitted_file( $path ), '   ' );

		$this->assertNotWPError( $attachment_id );
		$this->attachments[] = $attachment_id;

		$this->assertSame( pathinfo( $path, PATHINFO_FILENAME ), get_post( $attachment_id )->post_title );
	}

	/**
	 * A pledge awaiting confirmation is a draft; one an administrator has deactivated, or that has been
	 * trashed, is not awaiting anything, and a confirmation link must not bring it back into view.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeForm\accepts_email_confirmation
	 */
	public function test_only_a_live_pledge_accepts_email_confirmation(): void {
		foreach ( array( 'draft', 'pending', 'publish' ) as $status ) {
			$this->assertTrue( PledgeForm\accepts_email_confirmation( $this->create_pledge( $status ) ), $status );
		}

		foreach ( array( Pledge\DEACTIVE_STATUS, 'trash' ) as $status ) {
			$this->assertFalse( PledgeForm\accepts_email_confirmation( $this->create_pledge( $status ) ), $status );
		}
	}

	/**
	 * What the file says about itself is not kept alongside the attachment either, where a later consumer
	 * would find it still carrying whatever the post kses profile allows.
	 *
	 * @covers WordPressDotOrg\FiveForTheFuture\PledgeForm\discard_image_metadata_text
	 */
	public function test_logo_metadata_is_not_stored_with_the_attachment(): void {
		$image_meta = wp_get_attachment_metadata( $this->upload_logo( 'zzCANARY', 'Example Org' ) )['image_meta'];

		$this->assertSame( '', $image_meta['caption'] );
		$this->assertSame( '', $image_meta['title'] );
	}
}
