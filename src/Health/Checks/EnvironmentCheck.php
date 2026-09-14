<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Health\Checks;

use Flexa\MailBridge\Health\CheckResult;
use Flexa\MailBridge\Health\Contracts\HealthCheck;
use Flexa\MailBridge\Mailer\Contracts\MailerInterface;
use Flexa\MailBridge\Mailer\MailerRegistry;
use Flexa\MailBridge\Support\Settings;
use ReflectionException;
use ReflectionFunction;

defined( 'ABSPATH' ) || exit;

/**
 * Local, no-network checks of the WordPress sending setup: is a real mailer
 * selected and configured, is a valid From address in place, is dev-mode
 * (delivery disabled) on, and has another plugin overridden wp_mail() out from
 * under us. Cheap enough to run anywhere, but still only invoked via the health
 * runner for a single, consistent report.
 */
final class EnvironmentCheck implements HealthCheck {
	public function id(): string {
		return 'environment';
	}

	public function label(): string {
		return __( 'WordPress & sending setup', 'flexa-mailbridge' );
	}

	/**
	 * @return list<CheckResult>
	 */
	public function run(): array {
		return [
			$this->mailer_result(),
			$this->from_result(),
			$this->dev_mode_result(),
			$this->wp_mail_result(),
		];
	}

	private function mailer_result(): CheckResult {
		$slug = (string) Settings::get( 'current_mailer' );
		if ( '' === $slug ) {
			$slug = 'mail';
		}

		if ( 'mail' === $slug ) {
			return $this->row(
				'active_mailer',
				__( 'Active mailer', 'flexa-mailbridge' ),
				CheckResult::WARN,
				__( 'WordPress is using the built-in PHP mail() function. Delivery and deliverability are unreliable without an SMTP or API provider.', 'flexa-mailbridge' ),
				__( 'Choose and configure an SMTP or API mailer on the settings screen.', 'flexa-mailbridge' ),
				[ 'mailer' => $slug ]
			);
		}

		$mailer = MailerRegistry::instance()->make( $slug );
		if ( ! $mailer instanceof MailerInterface ) {
			return $this->row(
				'active_mailer',
				__( 'Active mailer', 'flexa-mailbridge' ),
				CheckResult::ERROR,
				/* translators: %s: mailer slug. */
				sprintf( __( 'The selected mailer "%s" is not available.', 'flexa-mailbridge' ), $slug ),
				__( 'Select an available mailer on the settings screen.', 'flexa-mailbridge' ),
				[ 'mailer' => $slug ]
			);
		}

		if ( ! $mailer->is_configured() ) {
			return $this->row(
				'active_mailer',
				__( 'Active mailer', 'flexa-mailbridge' ),
				CheckResult::ERROR,
				/* translators: %s: mailer slug. */
				sprintf( __( 'The selected mailer "%s" is missing required credentials.', 'flexa-mailbridge' ), $slug ),
				__( 'Complete the credentials for this mailer on the settings screen.', 'flexa-mailbridge' ),
				[ 'mailer' => $slug ]
			);
		}

		return $this->row(
			'active_mailer',
			__( 'Active mailer', 'flexa-mailbridge' ),
			CheckResult::PASS,
			/* translators: %s: mailer slug. */
			sprintf( __( 'The "%s" mailer is selected and configured.', 'flexa-mailbridge' ), $slug ),
			'',
			[ 'mailer' => $slug ]
		);
	}

	private function from_result(): CheckResult {
		$from = (string) Settings::get( 'from_email' );

		if ( '' === $from ) {
			return $this->row(
				'from_address',
				__( 'From address', 'flexa-mailbridge' ),
				CheckResult::WARN,
				__( 'No From address is configured, so WordPress uses its default wordpress@yourdomain address, which many providers reject or mark as spam.', 'flexa-mailbridge' ),
				__( 'Set a From address on a domain you can authenticate.', 'flexa-mailbridge' )
			);
		}

		if ( ! is_email( $from ) ) {
			return $this->row(
				'from_address',
				__( 'From address', 'flexa-mailbridge' ),
				CheckResult::ERROR,
				__( 'The configured From address is not a valid email address.', 'flexa-mailbridge' ),
				__( 'Correct the From address on the settings screen.', 'flexa-mailbridge' )
			);
		}

		return $this->row(
			'from_address',
			__( 'From address', 'flexa-mailbridge' ),
			CheckResult::PASS,
			/* translators: %s: configured From email address. */
			sprintf( __( 'From address is set to %s.', 'flexa-mailbridge' ), $from ),
			'',
			[ 'from' => $from ]
		);
	}

	private function dev_mode_result(): CheckResult {
		if ( (bool) Settings::get( 'disable_delivery' ) ) {
			return $this->row(
				'dev_mode',
				__( 'Delivery mode', 'flexa-mailbridge' ),
				CheckResult::WARN,
				__( 'Development mode is on: messages are logged but not actually sent.', 'flexa-mailbridge' ),
				__( 'Turn off "Disable email delivery" when you want real emails to go out.', 'flexa-mailbridge' )
			);
		}

		return $this->row(
			'dev_mode',
			__( 'Delivery mode', 'flexa-mailbridge' ),
			CheckResult::PASS,
			__( 'Email delivery is enabled.', 'flexa-mailbridge' )
		);
	}

	private function wp_mail_result(): CheckResult {
		if ( ! function_exists( 'wp_mail' ) || ! class_exists( ReflectionFunction::class ) ) {
			return $this->row(
				'wp_mail_owner',
				__( 'wp_mail() ownership', 'flexa-mailbridge' ),
				CheckResult::NOT_CHECKED,
				__( 'Could not determine which code defines wp_mail().', 'flexa-mailbridge' )
			);
		}

		try {
			$file = ( new ReflectionFunction( 'wp_mail' ) )->getFileName();
		} catch ( ReflectionException $e ) {
			unset( $e );
			return $this->row(
				'wp_mail_owner',
				__( 'wp_mail() ownership', 'flexa-mailbridge' ),
				CheckResult::NOT_CHECKED,
				__( 'Could not determine which code defines wp_mail().', 'flexa-mailbridge' )
			);
		}

		// Core defines the pluggable wp_mail() in wp-includes. If it lives anywhere
		// else, another plugin has redefined it and our transport may be bypassed.
		$normalised = false !== $file ? wp_normalize_path( $file ) : '';
		if ( '' !== $normalised && ! str_contains( $normalised, '/wp-includes/' ) ) {
			return $this->row(
				'wp_mail_owner',
				__( 'wp_mail() ownership', 'flexa-mailbridge' ),
				CheckResult::WARN,
				__( 'Another plugin has overridden wp_mail(), so Flexa MailBridge may not control how mail is sent.', 'flexa-mailbridge' ),
				__( 'Deactivate the other mail plugin that redefines wp_mail() to avoid conflicts.', 'flexa-mailbridge' ),
				[ 'defined_in' => $this->relative_path( $normalised ) ]
			);
		}

		return $this->row(
			'wp_mail_owner',
			__( 'wp_mail() ownership', 'flexa-mailbridge' ),
			CheckResult::PASS,
			__( 'wp_mail() is provided by WordPress core, so Flexa MailBridge controls delivery.', 'flexa-mailbridge' )
		);
	}

	/**
	 * Trim an absolute path down to a plugin/theme-relative hint so the report
	 * never leaks the server's directory layout.
	 */
	private function relative_path( string $path ): string {
		$marker = '/wp-content/';
		$pos    = strpos( $path, $marker );

		return false !== $pos ? substr( $path, $pos + strlen( $marker ) ) : basename( $path );
	}

	/**
	 * @param array<string, scalar> $context
	 */
	private function row( string $id, string $label, string $status, string $message, string $remediation = '', array $context = [] ): CheckResult {
		return new CheckResult( $this->id(), $id, $label, $status, $message, $remediation, $context );
	}
}
