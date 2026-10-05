<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Admin;

use Flexa\MailBridge\Support\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Shared state for the Flexa FormFlow cross-promotion: whether it should be
 * shown at all, and the URLs the two surfaces link to. Both the Dashboard
 * notice ({@see FormFlowNotice}) and the banner inside the plugin screen read
 * from here, so the detection logic and the dismissal flag can never drift.
 */
final class FormFlowPromo {
	public const SLUG             = 'flexa-formflow';
	public const WPORG_URL        = 'https://wordpress.org/plugins/flexa-formflow/';
	public const DISMISSED_OPTION = 'flexa_mailbridge_formflow_notice_dismissed';
	public const DISMISS_ACTION   = 'flexa_mailbridge_dismiss_formflow';

	/**
	 * A single dismissal covers every surface: someone who said no on the
	 * Dashboard should not meet the same pitch inside the plugin.
	 */
	public static function should_show(): bool {
		if ( ! apply_filters( 'flexa_mailbridge.formflow_promo.enabled', true ) ) {
			return false;
		}

		if ( ! Capabilities::can_manage() || self::is_dismissed() ) {
			return false;
		}

		return ! self::is_installed();
	}

	public static function is_dismissed(): bool {
		return (bool) get_option( self::DISMISSED_OPTION );
	}

	public static function dismiss(): void {
		update_option( self::DISMISSED_OPTION, 1 );
	}

	/**
	 * FormFlow counts as installed whether or not it is active: someone who
	 * already has the files does not need to be told about it.
	 */
	public static function is_installed(): bool {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $basename ) {
			if ( str_starts_with( (string) $basename, self::SLUG . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * One-click install link, or an empty string when the user may not install
	 * plugins (they still get "Learn more").
	 */
	public static function install_url(): string {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return '';
		}

		return wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=' . self::SLUG ),
			'install-plugin_' . self::SLUG
		);
	}

	public static function icon_url(): string {
		return FLEXA_MAILBRIDGE_URL . 'assets/images/flexa-formflow.png';
	}

	/**
	 * Nonce-protected dismiss link, used by the Dashboard notice. $redirect_to
	 * is the admin URL the action is appended to.
	 */
	public static function dismiss_url( string $redirect_to ): string {
		return wp_nonce_url(
			add_query_arg( self::DISMISS_ACTION, '1', $redirect_to ),
			self::DISMISS_ACTION
		);
	}
}
