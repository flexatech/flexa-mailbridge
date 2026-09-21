<?php
/**
 * Dev-only demo data seeder for the Dashboard / Reports / Monitoring screens.
 *
 * Not shipped: the whole /scripts directory is excluded via .distignore.
 *
 *   Seed:   wp eval-file scripts/seed-demo.php
 *   Clear:  wp eval-file scripts/seed-demo.php clear
 *
 * Generates ~30 days of realistic email logs (mostly delivered, a few failed
 * with varied causes), plus open and click events, so the analytics screens
 * look populated for screenshots. Safe to re-run: it clears its own rows first.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run through WP-CLI: wp eval-file scripts/seed-demo.php\n" );
	exit( 1 );
}

global $wpdb;

$t_logs   = $wpdb->prefix . 'flexa_mailbridge_email_logs';
$t_opens  = $wpdb->prefix . 'flexa_mailbridge_open_events';
$t_clicks = $wpdb->prefix . 'flexa_mailbridge_click_events';

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- one-off local dev seeder.
$wpdb->query( "TRUNCATE TABLE {$t_logs}" );
$wpdb->query( "TRUNCATE TABLE {$t_opens}" );
$wpdb->query( "TRUNCATE TABLE {$t_clicks}" );

$mode = isset( $args[0] ) ? (string) $args[0] : '';
if ( 'clear' === $mode ) {
	WP_CLI::success( 'Demo data cleared.' );
	return;
}

/** Weighted pick from [value => weight]. */
$pick = static function ( array $map ): string {
	$roll = wp_rand( 1, (int) array_sum( $map ) );
	foreach ( $map as $key => $weight ) {
		$roll -= (int) $weight;
		if ( $roll <= 0 ) {
			return (string) $key;
		}
	}
	return (string) array_key_first( $map );
};

$mailers    = [
	'sendgrid' => 40,
	'smtp'     => 25,
	'mailgun'  => 20,
	'gmail'    => 15,
];
$categories = [ 'provider_rejection', 'invalid_recipient', 'timeout', 'auth', 'rate_limit', 'connection' ];
$subjects   = [
	'Your order #%d has shipped',
	'Order #%d confirmed',
	'Your invoice #%d is ready',
	'Password reset requested',
	'Welcome to Flexa Store',
	'Your weekly newsletter',
	'You left items in your cart',
	'Refund for order #%d processed',
	'Your account details were updated',
	'Thanks for your recent review',
];
$urls       = [
	home_url( '/shop/' ),
	home_url( '/my-account/orders/' ),
	home_url( '/product/best-seller/' ),
	home_url( '/cart/' ),
	home_url( '/blog/summer-sale/' ),
];
$first      = [ 'James', 'Mai', 'Linh', 'John', 'Sarah', 'Minh', 'Emma', 'David', 'Hoa', 'Alex' ];
$last       = [ 'Nguyen', 'Smith', 'Tran', 'Le', 'Brown', 'Pham', 'Wilson', 'Vo', 'Garcia', 'Davis' ];
$from_email = 'store@' . wp_parse_url( home_url(), PHP_URL_HOST );

$now       = (int) current_time( 'timestamp' );
$days      = 30;
$log_total = 0;
$fail_total = 0;
$open_total = 0;
$click_total = 0;

for ( $d = $days - 1; $d >= 0; $d-- ) {
	$day_start = strtotime( gmdate( 'Y-m-d 00:00:00', $now - $d * DAY_IN_SECONDS ) );
	$dow       = (int) gmdate( 'N', $day_start );

	// A gentle upward trend toward recent days makes the chart look alive.
	$trend = 0.75 + ( ( $days - 1 - $d ) / ( $days - 1 ) ) * 0.55;
	$base  = (int) round( wp_rand( 26, 44 ) * $trend );
	if ( $dow >= 6 ) {
		$base = (int) round( $base * 0.55 ); // Quieter weekends.
	}

	$failed = (int) round( $base * wp_rand( 2, 7 ) / 100 );

	for ( $n = 0; $n < $base + $failed; $n++ ) {
		$is_fail  = $n >= $base;
		$mailer   = $pick( $mailers );
		$ts       = $day_start + wp_rand( 6 * HOUR_IN_SECONDS, 22 * HOUR_IN_SECONDS );
		$order_id = wp_rand( 1000, 4999 );
		$subject  = sprintf( $subjects[ array_rand( $subjects ) ], $order_id );
		$name     = $first[ array_rand( $first ) ] . ' ' . $last[ array_rand( $last ) ];
		$email    = strtolower( str_replace( ' ', '.', $name ) ) . wp_rand( 1, 99 ) . '@example.com';
		$to       = maybe_serialize( [ [ 'address' => $email, 'name' => $name ] ] );

		$wpdb->insert(
			$t_logs,
			[
				'subject'        => $subject,
				'email_from'     => $from_email,
				'email_to'       => $to,
				'mailer'         => $mailer,
				'status'         => $is_fail ? 0 : 1,
				'content_type'   => 'text/html',
				'body_content'   => '<p>' . esc_html( $subject ) . '</p>',
				'reason_error'   => $is_fail ? 'SMTP error from remote server' : '',
				'source'         => 'woocommerce',
				'error_category' => $is_fail ? $categories[ array_rand( $categories ) ] : '',
				'response_code'  => $is_fail ? (string) [ 421, 450, 550, 554 ][ array_rand( [ 421, 450, 550, 554 ] ) ] : '250',
				'duration_ms'    => wp_rand( 120, 1400 ),
				'retry_count'    => $is_fail ? wp_rand( 0, 2 ) : 0,
				'date_time'      => gmdate( 'Y-m-d H:i:s', $ts ),
			],
			[ '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' ]
		);
		++$log_total;

		if ( $is_fail ) {
			++$fail_total;
			continue;
		}

		$log_id = (int) $wpdb->insert_id;

		// ~62% of delivered mail gets opened.
		if ( wp_rand( 1, 100 ) <= 62 ) {
			$opens = wp_rand( 1, 4 );
			$wpdb->insert(
				$t_opens,
				[
					'log_id'    => $log_id,
					'count'     => $opens,
					'date_time' => gmdate( 'Y-m-d H:i:s', $ts + wp_rand( 300, 7200 ) ),
				],
				[ '%d', '%d', '%s' ]
			);
			$open_total += $opens;

			// ~30% of opened mail gets a click.
			if ( wp_rand( 1, 100 ) <= 30 ) {
				$link_count = wp_rand( 1, 2 );
				for ( $c = 0; $c < $link_count; $c++ ) {
					$hits = wp_rand( 1, 3 );
					$wpdb->insert(
						$t_clicks,
						[
							'log_id'    => $log_id,
							'url'       => $urls[ array_rand( $urls ) ],
							'count'     => $hits,
							'date_time' => gmdate( 'Y-m-d H:i:s', $ts + wp_rand( 600, 9000 ) ),
						],
						[ '%d', '%s', '%d', '%s' ]
					);
					$click_total += $hits;
				}
			}
		}
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery

WP_CLI::success(
	sprintf(
		'Seeded %d logs (%d failed), %d opens, %d clicks across %d days.',
		$log_total,
		$fail_total,
		$open_total,
		$click_total,
		$days
	)
);
