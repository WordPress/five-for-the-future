<?php
/**
 * Tests for the plain-text emails.
 */

declare( strict_types = 1 );

use WordPressDotOrg\FiveForTheFuture\{ Contributor, Email, Pledge, PledgeMeta };

defined( 'WPINC' ) || die();

/**
 * @group email
 */
class Test_Email extends WP_UnitTestCase {
	/**
	 * Run before every test.
	 */
	public function set_up() {
		parent::set_up();
		reset_phpmailer_instance();
	}

	/**
	 * Create a pledge the way the pledge form does: the typed name goes through the `org-name` sanitize callback,
	 * and then becomes the post title.
	 *
	 * @param string $typed_name The organization name as typed in the form.
	 *
	 * @return WP_Post
	 */
	protected function create_pledge( string $typed_name ): WP_Post {
		$name      = sanitize_meta( PledgeMeta\META_PREFIX . 'org-name', $typed_name, 'post', Pledge\CPT_ID );
		$pledge_id = self::factory()->post->create( array(
			'post_type'   => Pledge\CPT_ID,
			'post_status' => 'publish',
			'post_title'  => $name,
		) );

		update_post_meta( $pledge_id, PledgeMeta\META_PREFIX . 'org-pledge-email', 'admin@example.org' );

		return get_post( $pledge_id );
	}

	/**
	 * Send one of the emails that name the pledge.
	 *
	 * The contributor is the install's admin account. Creating a user here would shift the user IDs that
	 * `Test_Stats::test_get_snapshot()` expects.
	 *
	 * @param string  $email  Which email to send.
	 * @param WP_Post $pledge The pledge it is about.
	 *
	 * @return void
	 */
	protected function send( string $email, WP_Post $pledge ): void {
		$user        = get_userdata( 1 );
		$contributor = get_post(
			self::factory()->post->create( array(
				'post_type'   => Contributor\CPT_ID,
				'post_status' => 'pending',
				'post_parent' => $pledge->ID,
				'post_title'  => $user->user_login,
			) )
		);

		reset_phpmailer_instance();

		switch ( $email ) {
			case 'contributor confirmation':
				Email\send_contributor_confirmation_emails( $pledge->ID );
				break;

			case 'contributor removed':
				Email\send_contributor_removed_email( $pledge->ID, $contributor );
				break;

			case 'pledge deactivated':
				Email\send_pledge_deactivation_email( $pledge );
				break;

			case 'pledge review reminder':
				Email\send_pledge_update_email( $pledge );
				break;

			case 'pledge inactive':
				Email\send_pledge_inactive_email( $pledge );
				break;
		}
	}

	/**
	 * The pledge form saves "Smith & Co" as the title "Smith &amp; Co". Every plain-text email that names the
	 * pledge must show the name as it was typed.
	 *
	 * @dataProvider data_emails_that_name_the_pledge
	 * @covers WordPressDotOrg\FiveForTheFuture\Email\send_contributor_confirmation_emails
	 * @covers WordPressDotOrg\FiveForTheFuture\Email\send_contributor_removed_email
	 * @covers WordPressDotOrg\FiveForTheFuture\Email\send_pledge_deactivation_email
	 * @covers WordPressDotOrg\FiveForTheFuture\Email\send_pledge_update_email
	 * @covers WordPressDotOrg\FiveForTheFuture\Email\send_pledge_inactive_email
	 *
	 * @param string $email            Which email to send.
	 * @param string $expected_subject The subject it should have.
	 * @param string $expected_text    Text the body should contain.
	 *
	 * @return void
	 */
	public function test_emails_show_the_organization_name_as_typed( string $email, string $expected_subject, string $expected_text ): void {
		$pledge = $this->create_pledge( 'Smith & Co' );
		$this->assertSame( 'Smith &amp; Co', $pledge->post_title, 'The stored title should carry the entity.' );

		$this->send( $email, $pledge );
		$sent = tests_retrieve_phpmailer_instance()->get_sent();

		$this->assertNotFalse( $sent, 'No email was sent.' );
		$this->assertSame( $expected_subject, $sent->subject );
		$this->assertStringContainsString( $expected_text, $sent->body );
		$this->assertStringNotContainsString( '&amp;', $sent->subject . $sent->body );
	}

	/**
	 * The emails that put the pledge name in the subject or the body.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function data_emails_that_name_the_pledge(): array {
		return array(
			'contributor confirmation' => array(
				'contributor confirmation',
				'Confirm your Smith & Co sponsorship',
				"If Smith & Co isn't sponsoring your contributions",
			),
			'contributor removed'      => array(
				'contributor removed',
				'Removed from Smith & Co Five for the Future pledge',
				'no longer linked to Smith & Co’s Five for the Future pledge',
			),
			'pledge deactivated'       => array(
				'pledge deactivated',
				'Pledge removed from Five for the Future',
				'Your organization, Smith & Co, has been removed',
			),
			'pledge review reminder'   => array(
				'pledge review reminder',
				'Please review your Five for the Future pledge',
				'Hi Smith & Co, this is a bi-annual reminder',
			),
			'pledge inactive'          => array(
				'pledge inactive',
				'Please review your Five for the Future pledge',
				'Hi Smith & Co, we noticed',
			),
		);
	}

	/**
	 * Other names come back as typed too, including "<", which the name sanitizer stores as "&lt;".
	 *
	 * @dataProvider data_names_with_special_characters
	 * @covers WordPressDotOrg\FiveForTheFuture\Email\send_pledge_deactivation_email
	 *
	 * @param string $typed_name The organization name as typed in the form.
	 *
	 * @return void
	 */
	public function test_special_characters_in_the_name_are_shown_as_typed( string $typed_name ): void {
		$pledge = $this->create_pledge( $typed_name );

		$this->send( 'pledge deactivated', $pledge );

		$this->assertStringContainsString(
			"Your organization, $typed_name, has been removed",
			tests_retrieve_phpmailer_instance()->get_sent()->body
		);
	}

	/**
	 * Names with characters that are stored as entities, and two that are stored as typed.
	 *
	 * @return array<string, array{string}>
	 */
	public function data_names_with_special_characters(): array {
		return array(
			'less-than'       => array( 'Bits < Atoms' ),
			'ampersand twice' => array( 'Smith & Jones & Co' ),
			'quotes'          => array( 'Joe\'s "Best" Diner' ),
			'plain name'      => array( 'Acme Hosting' ),
		);
	}
}
