<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Maintenance;

use Flexa\MailBridge\Database\Schema;
use Flexa\MailBridge\Health\HealthChecker;
use Flexa\MailBridge\Import\ImporterRegistry;
use Flexa\MailBridge\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Wipes all Flexa MailBridge data. The single destructive path — used by the REST
 * danger zone (DangerZoneEndpoint) and the `wp flexa-mailbridge reset` CLI command —
 * so the two can never drift. Never inline a second DELETE anywhere else.
 */
final class Eraser {
	/**
	 * @return array{settings_removed:bool, tables_dropped:bool}
	 */
	public static function erase_all(): array {
		$removed = delete_option( Settings::OPTION_KEY );

		// Clear import bookkeeping so a fresh install can re-detect and re-import.
		delete_option( ImporterRegistry::IMPORTED_LOGS_OPTION );
		delete_option( 'flexa_mailbridge_import_notice_dismissed' );

		// Clear the cached health report.
		delete_option( HealthChecker::OPTION );

		$dropped = false;
		if ( class_exists( Schema::class ) ) {
			Schema::drop();
			$dropped = true;
		}

		do_action( 'flexa_mailbridge.data_reset' );

		return [
			'settings_removed' => (bool) $removed,
			'tables_dropped'   => $dropped,
		];
	}
}
