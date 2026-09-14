<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Deactivation is intentionally non-destructive: settings and email logs
 * survive so a re-activation restores the prior state. Full teardown happens
 * only on uninstall (uninstall.php) or via the danger zone (Maintenance\Eraser).
 */
final class Deactivator {
	public static function deactivate(): void {
		// Clear any scheduled cron events registered by Reports\Scheduler (WP7),
		// HealthChecker (DL2), and the delivery queue (DL4).
		foreach ( [
			'flexa_mailbridge_retention',
			'flexa_mailbridge_report_weekly',
			'flexa_mailbridge_report_monthly',
			'flexa_mailbridge_health',
			'flexa_mailbridge_queue_run',
			'flexa_mailbridge_queue_tick',
		] as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}

		// Drop any pending Action Scheduler runs of the queue worker, if present.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'flexa_mailbridge_queue_run', [], 'flexa-mailbridge' );
		}
	}
}
