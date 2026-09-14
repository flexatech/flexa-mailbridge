<?php

declare(strict_types=1);

namespace Flexa\MailBridge\Setup;

use Flexa\MailBridge\Database\Schema;
use Flexa\MailBridge\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class Activator {
	public static function activate(): void {
		if ( class_exists( Schema::class ) ) {
			Schema::migrate();
		}

		if ( get_option( Settings::OPTION_KEY, null ) === null ) {
			add_option( Settings::OPTION_KEY, Settings::defaults() );
		}
	}
}
