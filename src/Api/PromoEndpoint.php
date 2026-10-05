<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Api;

use Flexa\MailBridge\Admin\FormFlowPromo;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * POST /promo/dismiss stores the "no thanks" for the FormFlow cross-promotion.
 * The admin app calls this when the banner is closed; the flag is the same one
 * the Dashboard notice uses, so dismissing either surface hides both.
 */
final class PromoEndpoint extends Endpoint {
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/promo/dismiss',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'dismiss' ],
					'permission_callback' => [ $this, 'manage_permission' ],
				],
			]
		);
	}

	public function dismiss( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		FormFlowPromo::dismiss();

		return new WP_REST_Response( [ 'ok' => true ] );
	}
}
