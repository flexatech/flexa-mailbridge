<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Support;

use Flexa\MailBridge\Concerns\HasInstance;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the reusable Deactivation Intelligence client SDK into the plugin.
 *
 * Runs only on the Plugins screen and never blocks deactivation. The bundled
 * SDK lives outside the PSR-4 namespace, so it is required explicitly.
 */
final class DeactivationSurvey {

	use HasInstance;

	/** Central platform base URL (no trailing slash). */
	private const API_URL = 'https://product-intelligence.flexacommerce.com';

	public function register(): void {
		if ( ! apply_filters( 'flexa_mailbridge/deactivation_survey/enabled', true ) ) {
			return;
		}

		$sdk = FLEXA_MAILBRIDGE_PATH . 'libraries/deactivation-intelligence/src/class-deactivation-intelligence.php';
		if ( ! is_readable( $sdk ) ) {
			return;
		}
		require_once $sdk;

		if ( ! class_exists( \Deactivation_Intelligence::class ) ) {
			return;
		}

		\Deactivation_Intelligence::init(
			apply_filters(
				'flexa_mailbridge/deactivation_survey/config',
				array(
					'product'     => 'flexa-mailbridge',
					'tier'        => 'free',
					'version'     => FLEXA_MAILBRIDGE_VERSION,
					'plugin_file' => FLEXA_MAILBRIDGE_BASENAME,
					'api_url'     => self::API_URL,
				)
			)
		);
	}
}
