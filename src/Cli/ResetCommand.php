<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Cli;

use Flexa\MailBridge\Maintenance\Eraser;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Wipe all Flexa MailBridge data — settings, log tables and import bookkeeping.
 * Routes through the shared Maintenance\Eraser path so the CLI and the REST
 * danger zone can never drift. Destructive and irreversible.
 *
 *     wp flexa-mailbridge reset [--yes]
 */
final class ResetCommand {
	public static function register(): void {
		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}
		WP_CLI::add_command( 'flexa-mailbridge reset', self::class );
	}

	/**
	 * Delete every Flexa MailBridge setting and log. Cannot be undone.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp flexa-mailbridge reset
	 *     wp flexa-mailbridge reset --yes
	 *
	 * @param array<int, string>    $args
	 * @param array<string, string> $assoc
	 * @when after_wp_load
	 */
	public function __invoke( array $args, array $assoc ): void {
		unset( $args );

		WP_CLI::confirm( 'This permanently deletes all Flexa MailBridge settings and logs. Continue?', $assoc );

		$result = Eraser::erase_all();

		WP_CLI::success(
			sprintf(
				'Flexa MailBridge reset complete. Settings removed: %s. Log tables dropped: %s.',
				$result['settings_removed'] ? 'yes' : 'no',
				$result['tables_dropped'] ? 'yes' : 'no'
			)
		);
	}
}
