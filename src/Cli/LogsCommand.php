<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Cli;

use Flexa\MailBridge\Domain\EmailLog;
use Flexa\MailBridge\Domain\EmailLogRepository;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Read the email log from the command line.
 *
 *     wp flexa-mailbridge log list [--status=<status>] [--mailer=<mailer>] [--search=<term>] [--format=<format>]
 *     wp flexa-mailbridge log get <id>
 */
final class LogsCommand {
	public static function register(): void {
		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}
		WP_CLI::add_command( 'flexa-mailbridge log', self::class );
	}

	/**
	 * List logged emails, newest first.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Filter by delivery status. One of sent, failed, pending, queued, retrying,
	 * cancelled (or 0-5).
	 *
	 * [--mailer=<mailer>]
	 * : Filter by the mailer slug that sent the message (e.g. smtp, sendgrid).
	 *
	 * [--search=<term>]
	 * : Match the term against subject, sender and recipients.
	 *
	 * [--date_from=<datetime>]
	 * : Only rows at or after this UTC datetime (Y-m-d or Y-m-d H:i:s).
	 *
	 * [--date_to=<datetime>]
	 * : Only rows at or before this UTC datetime (Y-m-d or Y-m-d H:i:s).
	 *
	 * [--limit=<n>]
	 * : Maximum rows to return. Default 20, max 1000.
	 *
	 * [--offset=<n>]
	 * : Skip this many rows (paging). Default 0.
	 *
	 * [--format=<format>]
	 * : Render format. One of table, csv, json, yaml, ids, count. Default table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp flexa-mailbridge log list
	 *     wp flexa-mailbridge log list --status=failed --format=csv
	 *     wp flexa-mailbridge log list --mailer=sendgrid --limit=100 --format=json
	 *
	 * @param array<int, string>    $args
	 * @param array<string, string> $assoc
	 * @when after_wp_load
	 */
	public function list( array $args, array $assoc ): void {
		unset( $args );

		$repo = new EmailLogRepository();

		$query = [
			'limit'  => max( 1, min( 1000, (int) ( $assoc['limit'] ?? 20 ) ) ),
			'offset' => max( 0, (int) ( $assoc['offset'] ?? 0 ) ),
		];

		if ( isset( $assoc['status'] ) && '' !== $assoc['status'] ) {
			$query['status'] = $this->resolve_status( (string) $assoc['status'] );
		}
		foreach ( [ 'mailer', 'search', 'date_from', 'date_to' ] as $key ) {
			if ( isset( $assoc[ $key ] ) && '' !== $assoc[ $key ] ) {
				$query[ $key ] = (string) $assoc[ $key ];
			}
		}

		$format = strtolower( (string) ( $assoc['format'] ?? 'table' ) );

		if ( 'count' === $format ) {
			WP_CLI::line( (string) $repo->count( $query ) );
			return;
		}

		$logs = $repo->query( $query );
		$rows = array_map(
			static function ( EmailLog $log ): array {
				$to = array_map(
					static fn ( array $r ): string => $r['address'],
					$log->email_to
				);

				return [
					'id'        => $log->id,
					'date_time' => $log->date_time,
					'status'    => $log->status_label(),
					'mailer'    => $log->mailer,
					'from'      => $log->email_from,
					'to'        => implode( ', ', array_filter( $to ) ),
					'subject'   => $log->subject,
				];
			},
			$logs
		);

		if ( 'ids' === $format ) {
			WP_CLI::line( implode( ' ', array_map( static fn ( array $r ): string => (string) $r['id'], $rows ) ) );
			return;
		}

		if ( [] === $rows ) {
			WP_CLI::success( 'No matching log entries.' );
			return;
		}

		\WP_CLI\Utils\format_items(
			$format,
			$rows,
			[ 'id', 'date_time', 'status', 'mailer', 'from', 'to', 'subject' ]
		);
	}

	/**
	 * Show one logged email in full, including the body.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The log entry id.
	 *
	 * [--format=<format>]
	 * : Render format. One of table, json, yaml. Default table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp flexa-mailbridge log get 42
	 *     wp flexa-mailbridge log get 42 --format=json
	 *
	 * @param array<int, string>    $args
	 * @param array<string, string> $assoc
	 * @when after_wp_load
	 */
	public function get( array $args, array $assoc ): void {
		$id  = (int) ( $args[0] ?? 0 );
		$log = ( new EmailLogRepository() )->find( $id );

		if ( null === $log ) {
			WP_CLI::error( sprintf( 'No log entry found for id %d.', $id ) );
		}

		$to = implode(
			', ',
			array_filter(
				array_map(
					static fn ( array $r ): string => $r['address'],
					$log->email_to
				)
			)
		);

		$fields = [
			[
				'field' => 'id',
				'value' => (string) $log->id,
			],
			[
				'field' => 'date_time',
				'value' => $log->date_time,
			],
			[
				'field' => 'status',
				'value' => $log->status_label(),
			],
			[
				'field' => 'mailer',
				'value' => $log->mailer,
			],
			[
				'field' => 'from',
				'value' => $log->email_from,
			],
			[
				'field' => 'to',
				'value' => $to,
			],
			[
				'field' => 'subject',
				'value' => $log->subject,
			],
			[
				'field' => 'content_type',
				'value' => $log->content_type,
			],
			[
				'field' => 'source',
				'value' => $log->source,
			],
			[
				'field' => 'reason_error',
				'value' => $log->reason_error,
			],
			[
				'field' => 'body_content',
				'value' => $log->body_content,
			],
		];

		$format = strtolower( (string) ( $assoc['format'] ?? 'table' ) );
		\WP_CLI\Utils\format_items( $format, $fields, [ 'field', 'value' ] );
	}

	/**
	 * Accept either a status name (see EmailLog::STATUS_LABELS) or a raw integer.
	 */
	private function resolve_status( string $status ): int {
		$key     = strtolower( trim( $status ) );
		$by_name = array_flip( EmailLog::STATUS_LABELS );
		if ( isset( $by_name[ $key ] ) ) {
			return $by_name[ $key ];
		}
		if ( is_numeric( $status ) ) {
			return (int) $status;
		}

		WP_CLI::error( sprintf( 'Unknown status "%s". Use one of: %s.', $status, implode( ', ', EmailLog::STATUS_LABELS ) ) );

		return EmailLog::STATUS_FAILED; // Unreachable: WP_CLI::error() halts execution.
	}
}
