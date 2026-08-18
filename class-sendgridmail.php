<?php
/*
 * Summary
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */

use SendGrid\Mail\Mail;

/**
 * Helper class for sending email via Twilio SendGrid
 */
class SendGridMail {

	/**
	 * Used to send an email.
	 *
	 * @param  mixed   $email_address Email address of the recipient.
	 * @param  mixed   $subject subject for the email.
	 * @param  mixed   $message Message body for the email.
	 * @param  boolean $class_email Used to get the correct From email address.
	 * @param  mixed   $reply_to Used to set the reply to field.
	 * @return True on success, false on failure.
	 */
	public function send_mail( $email_address, $subject, $message, $class_email = false, $reply_to = null ) {
		$email = new Mail();

		if ( $class_email ) {
			$email->setFrom(
				'classes@scwwoodshop.com',
				'SCW WoodClub Classes'
			);
		} else {
			$email->setFrom(
				'monitors@scwwoodshop.com',
				'SCW WoodClub Signups'
			);
		}

		if ( $reply_to ) {
			$email->setReplyTo( $reply_to );
		}

		$email->setSubject( $subject );
		$email->addTo( $email_address );

		$email->addContent(
			'text/html',
			$message
		);

		$sendgrid_api_key = get_option( 'signups_sendgrid' ) ? get_option( 'signups_sendgrid' )['sendgrid_api_key'] : '';

		$sendgrid = new \SendGrid( $sendgrid_api_key );

		// Skip sending emails on non-production servers unless explicitly enabled for testing
		$is_production = ( isset( $_SERVER['HTTP_HOST'] ) && strpos( $_SERVER['HTTP_HOST'], 'scwwoodshop.com' ) !== false );
		$force_send    = defined( 'SIGNUPS_FORCE_SEND_EMAIL' ) && SIGNUPS_FORCE_SEND_EMAIL === true;

		if ( ! $is_production && ! $force_send ) {
			if ( 'ecsproull765@gmail.com' !== $email_address ) {
				return true; // Return true to avoid breaking calling code.
			}
		}

		try {
			$response = $sendgrid->send( $email );
			$status   = (int) $response->statusCode();
			$body     = (string) $response->body();

			if ( 202 !== $status ) {
				return false;
			}

			return true;
		} catch ( \Throwable $e ) {
			return false;
		}
	}
}
