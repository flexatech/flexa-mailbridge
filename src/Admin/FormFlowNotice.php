<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Admin;

use Flexa\MailBridge\Concerns\HasInstance;
use Flexa\MailBridge\Support\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Cross-promotion notice on the WordPress Dashboard suggesting Flexa FormFlow,
 * our free form builder. Shown only while FormFlow is not installed, and only
 * until the user dismisses it (the choice is stored, so it never comes back).
 * Visibility and URLs come from {@see FormFlowPromo}.
 */
final class FormFlowNotice {
	use HasInstance;

	public function register(): void {
		add_action( 'admin_init', [ $this, 'maybe_dismiss' ] );
		add_action( 'admin_notices', [ $this, 'render' ] );
	}

	public function maybe_dismiss(): void {
		if ( empty( $_GET[ FormFlowPromo::DISMISS_ACTION ] ) || ! Capabilities::can_manage() ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, FormFlowPromo::DISMISS_ACTION ) ) {
			return;
		}

		FormFlowPromo::dismiss();

		wp_safe_redirect( admin_url( 'index.php' ) );
		exit;
	}

	public function render(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		$dismiss_url = FormFlowPromo::dismiss_url( admin_url( 'index.php' ) );
		$install_url = FormFlowPromo::install_url();

		$this->print_styles();
		?>
		<div class="notice notice-info is-dismissible flexa-mb-promo" data-dismiss-url="<?php echo esc_url( $dismiss_url ); ?>">
			<div class="flexa-mb-promo__inner">
				<div class="flexa-mb-promo__icon" aria-hidden="true"><?php $this->print_icon(); ?></div>
				<div class="flexa-mb-promo__body">
					<h3 class="flexa-mb-promo__title">
						<?php esc_html_e( 'Need forms, email templates and workflows to go with your setup?', 'flexa-mailbridge' ); ?>
					</h3>
					<p class="flexa-mb-promo__text">
						<?php esc_html_e( 'Flexa FormFlow is our free form builder with a visual email designer and a workflow engine built in. Drag fields onto the canvas, design the emails they trigger, then add conditional steps that run on each submission. MailBridge handles the routing, logs and tracking.', 'flexa-mailbridge' ); ?>
					</p>
					<p class="flexa-mb-promo__actions">
						<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button">
							<?php esc_html_e( 'Not interested', 'flexa-mailbridge' ); ?>
						</a>
						<a href="<?php echo esc_url( FormFlowPromo::WPORG_URL ); ?>" class="button" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Learn more', 'flexa-mailbridge' ); ?>
						</a>
						<?php if ( '' !== $install_url ) : ?>
							<a href="<?php echo esc_url( $install_url ); ?>" class="button button-primary">
								<?php esc_html_e( 'Install Now', 'flexa-mailbridge' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
			</div>
		</div>
		<?php
		// The "x" button is injected by core's common.js after this markup, so
		// the listener is delegated from the notice itself.
		wp_print_inline_script_tag(
			"( function () {
				var notice = document.querySelector( '.flexa-mb-promo' );
				if ( ! notice ) {
					return;
				}
				notice.addEventListener( 'click', function ( event ) {
					if ( ! event.target.closest( '.notice-dismiss' ) ) {
						return;
					}
					var url = notice.getAttribute( 'data-dismiss-url' );
					if ( url ) {
						window.fetch( url, { credentials: 'same-origin' } );
					}
				} );
			} )();"
		);

		/**
		 * Claim the Dashboard slot so a sibling Flexa plugin running later on
		 * this same hook does not print a second copy of the pitch.
		 */
		do_action( FormFlowPromo::RENDERED_ACTION );
	}

	private function should_render(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen instanceof \WP_Screen || 'dashboard' !== $screen->id ) {
			return false;
		}

		// Another Flexa plugin already showed it on this page load.
		if ( did_action( FormFlowPromo::RENDERED_ACTION ) > 0 ) {
			return false;
		}

		return FormFlowPromo::should_show();
	}

	private function print_icon(): void {
		printf(
			'<img src="%s" width="64" height="64" alt="" decoding="async" />',
			esc_url( FormFlowPromo::icon_url() )
		);
	}

	private function print_styles(): void {
		?>
		<style>
			.flexa-mb-promo { border-left-color: #2271b1; padding: 4px 12px; }
			.flexa-mb-promo__inner { display: flex; gap: 16px; align-items: flex-start; padding: 12px 4px; }
			.flexa-mb-promo__icon { flex: 0 0 auto; line-height: 0; }
			.flexa-mb-promo__icon img { display: block; width: 64px; height: 64px; }
			.flexa-mb-promo__body { flex: 1 1 auto; min-width: 0; }
			.flexa-mb-promo__title { margin: 0 0 6px; font-size: 15px; line-height: 1.4; }
			.flexa-mb-promo__text { margin: 0 0 12px; max-width: 70em; }
			.flexa-mb-promo__actions { margin: 0; display: flex; flex-wrap: wrap; gap: 8px; }
			@media screen and (max-width: 782px) {
				.flexa-mb-promo__inner { gap: 12px; }
				.flexa-mb-promo__icon img { width: 44px; height: 44px; }
			}
		</style>
		<?php
	}
}
