<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Admin;

use Flexa\MailBridge\Concerns\HasInstance;
use Flexa\MailBridge\Reports\Stats;
use Flexa\MailBridge\Support\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * A compact "last 30 days" stats widget on the WP dashboard. Read-only glance;
 * the full charts live on the plugin's Reports tab.
 */
final class DashboardWidget {
	use HasInstance;

	public function register(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'add_widget' ] );
	}

	public function add_widget(): void {
		if ( ! Capabilities::can_manage() ) {
			return;
		}

		wp_add_dashboard_widget(
			'flexa_mailbridge_stats',
			__( 'Flexa MailBridge — Last 30 Days', 'flexa-mailbridge' ),
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		$totals = Stats::summary( 30 )['totals'];

		$cards = [
			__( 'Sent', 'flexa-mailbridge' )       => number_format_i18n( $totals['sent'] ),
			__( 'Failed', 'flexa-mailbridge' )     => number_format_i18n( $totals['failed'] ),
			__( 'Opens', 'flexa-mailbridge' )      => number_format_i18n( $totals['opens'] ),
			__( 'Clicks', 'flexa-mailbridge' )     => number_format_i18n( $totals['clicks'] ),
			__( 'Open rate', 'flexa-mailbridge' )  => $totals['open_rate'] . '%',
			__( 'Click rate', 'flexa-mailbridge' ) => $totals['click_rate'] . '%',
		];

		echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">';
		foreach ( $cards as $label => $value ) {
			printf(
				'<div style="background:#f6f7f7;border-radius:6px;padding:10px;text-align:center;"><div style="font-size:20px;font-weight:600;color:#1d2327;">%s</div><div style="font-size:11px;color:#646970;text-transform:uppercase;letter-spacing:.03em;">%s</div></div>',
				esc_html( (string) $value ),
				esc_html( (string) $label )
			);
		}
		echo '</div>';

		printf(
			'<p style="margin:12px 0 0;"><a href="%s">%s</a></p>',
			esc_url( admin_url( 'admin.php?page=' . Menu::SLUG ) ),
			esc_html__( 'View full reports →', 'flexa-mailbridge' )
		);
	}
}
