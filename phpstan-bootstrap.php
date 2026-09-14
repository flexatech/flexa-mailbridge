<?php
/**
 * PHPStan analysis bootstrap (NOT loaded at runtime).
 *
 * The plugin's FLEXA_MAILBRIDGE_* constants are defined at runtime in flexa-mailbridge.php
 * via define() inside a PHP-version guard, which static analysis cannot follow
 * across files. Declaring them here lets PHPStan resolve every FLEXA_MAILBRIDGE_*
 * reference instead of reporting constant.notFound.
 *
 * @package Flexa\MailBridge
 */

declare(strict_types=1);

defined( 'FLEXA_MAILBRIDGE_VERSION' ) || define( 'FLEXA_MAILBRIDGE_VERSION', '1.0.0' );
defined( 'FLEXA_MAILBRIDGE_FILE' ) || define( 'FLEXA_MAILBRIDGE_FILE', __DIR__ . '/flexa-mailbridge.php' );
defined( 'FLEXA_MAILBRIDGE_PATH' ) || define( 'FLEXA_MAILBRIDGE_PATH', __DIR__ . '/' );
defined( 'FLEXA_MAILBRIDGE_URL' ) || define( 'FLEXA_MAILBRIDGE_URL', 'https://example.test/wp-content/plugins/flexa-mailbridge/' );
defined( 'FLEXA_MAILBRIDGE_BASENAME' ) || define( 'FLEXA_MAILBRIDGE_BASENAME', 'flexa-mailbridge/flexa-mailbridge.php' );
defined( 'FLEXA_MAILBRIDGE_REST_NAMESPACE' ) || define( 'FLEXA_MAILBRIDGE_REST_NAMESPACE', 'flexa-mailbridge/v1' );
defined( 'FLEXA_MAILBRIDGE_TEXT_DOMAIN' ) || define( 'FLEXA_MAILBRIDGE_TEXT_DOMAIN', 'flexa-mailbridge' );

// WordPress core constant used when lazily requiring the bundled PHPMailer.
defined( 'WPINC' ) || define( 'WPINC', 'wp-includes' );
