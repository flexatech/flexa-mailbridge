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

	/**
	 * Put this plugin's copy of the SDK on the ballot.
	 *
	 * Called from the bootstrap at plugin-file load time. The SDK client is a
	 * single global class, so on a site running several Flexa plugins only one
	 * bundled copy can define it, and that copy also serves the JS and CSS for
	 * every product (its enqueue resolves them relative to its own file).
	 * `loader.php` turns the include into an offer: every copy registers its
	 * version, and the newest one is loaded at `plugins_loaded`. Registering
	 * after that point means this copy missed the vote and the site keeps
	 * running whatever older copy a neighbouring plugin shipped.
	 *
	 * Deliberately not gated on the `enabled` filter: a filter cannot be trusted
	 * to exist this early, and offering the file costs nothing. Whether the
	 * survey runs for THIS plugin is decided below; a copy that wins the vote
	 * while disabled here still serves the other products correctly.
	 */
	public static function preload(): void {
		$loader = FLEXA_MAILBRIDGE_PATH . 'libraries/deactivation-intelligence/src/loader.php';
		if ( is_readable( $loader ) ) {
			require_once $loader;
		}
	}

	public function register(): void {
		if ( ! apply_filters( 'flexa_mailbridge/deactivation_survey/enabled', true ) ) {
			return;
		}

		if ( ! function_exists( 'deactivation_intelligence_init' ) ) {
			return;
		}

		\deactivation_intelligence_init(
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
