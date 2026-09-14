<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Admin;

use Flexa\MailBridge\Concerns\HasInstance;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the top-level admin page that hosts the React app and adds a
 * "Settings" shortcut on the Plugins list row.
 */
final class Menu {
	use HasInstance;

	public const SLUG = 'flexa-mailbridge';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_filter( 'plugin_action_links_' . FLEXA_MAILBRIDGE_BASENAME, [ $this, 'action_links' ] );
	}

	/**
	 * @param array<int|string, string> $links
	 * @return array<int|string, string>
	 */
	public function action_links( array $links ): array {
		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ),
			esc_html__( 'Settings', 'flexa-mailbridge' )
		);

		array_unshift( $links, $settings );

		return $links;
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'Flexa MailBridge', 'flexa-mailbridge' ),
			__( 'Flexa MailBridge', 'flexa-mailbridge' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render_page' ],
			'dashicons-email-alt',
			81
		);
	}

	public function render_page(): void {
		$template = FLEXA_MAILBRIDGE_PATH . 'templates/admin-app.php';
		if ( is_readable( $template ) ) {
			require $template;
		}
	}
}
