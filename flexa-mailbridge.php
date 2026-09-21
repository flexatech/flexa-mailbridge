<?php
/**
 * Plugin Name:       Flexa MailBridge
 * Description:       WP Mail SMTP with email logs, open/click tracking, and reports.
 * Version:           1.0.2
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            FlexaTech
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flexa-mailbridge
 * Domain Path:       /i18n/languages
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Flexa MailBridge requires PHP 8.2 or higher. The plugin has been disabled.', 'flexa-mailbridge' );
			echo '</p></div>';
		}
	);
	return;
}

define( 'FLEXA_MAILBRIDGE_VERSION', '1.0.2' );
define( 'FLEXA_MAILBRIDGE_FILE', __FILE__ );
define( 'FLEXA_MAILBRIDGE_PATH', plugin_dir_path( __FILE__ ) );
define( 'FLEXA_MAILBRIDGE_URL', plugin_dir_url( __FILE__ ) );
define( 'FLEXA_MAILBRIDGE_BASENAME', plugin_basename( __FILE__ ) );
define( 'FLEXA_MAILBRIDGE_REST_NAMESPACE', 'flexa-mailbridge/v1' );
define( 'FLEXA_MAILBRIDGE_TEXT_DOMAIN', 'flexa-mailbridge' );

if ( file_exists( FLEXA_MAILBRIDGE_PATH . 'vendor/autoload.php' ) ) {
	require_once FLEXA_MAILBRIDGE_PATH . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = 'Flexa\MailBridge\\';
			if ( ! str_starts_with( $class, $prefix ) ) {
				return;
			}
			$relative = substr( $class, strlen( $prefix ) );
			$file     = FLEXA_MAILBRIDGE_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( is_readable( $file ) ) {
				require $file;
			}
		}
	);
}

register_activation_hook( __FILE__, [ \Flexa\MailBridge\Setup\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \Flexa\MailBridge\Setup\Deactivator::class, 'deactivate' ] );

add_action(
	'plugins_loaded',
	static function (): void {
		\Flexa\MailBridge\Plugin::instance()->boot();
	}
);
