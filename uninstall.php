<?php
/**
 * Uninstall handler. Removes every trace of Flexa MailBridge: the settings option,
 * the DB version marker, and all flexa_mailbridge_* tables. Runs in isolation (the
 * plugin is NOT bootstrapped during uninstall) so it cannot use the plugin's
 * classes — the option key and table list below are deliberately duplicated
 * from Flexa\MailBridge\Support\Settings and Flexa\MailBridge\Database\Schema. Keep in sync.
 *
 * @package Flexa\MailBridge
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'flexa_mailbridge_settings' );
delete_option( 'flexa_mailbridge_db_version' );
delete_option( 'flexa_mailbridge_health_report' );

$flexa_mailbridge_tables = [
	'flexa_mailbridge_email_logs',
	'flexa_mailbridge_open_events',
	'flexa_mailbridge_click_events',
	'flexa_mailbridge_email_queue',
];

foreach ( $flexa_mailbridge_tables as $flexa_mailbridge_table ) {
	$flexa_mailbridge_name = $wpdb->prefix . $flexa_mailbridge_table;
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- table name is a hard-coded literal, not user input; dropping the plugin's own tables on uninstall, where caching does not apply to a one-off DDL statement.
	$wpdb->query( "DROP TABLE IF EXISTS {$flexa_mailbridge_name}" );
}
