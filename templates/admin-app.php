<?php
/**
 * Mount point for the Flexa MailBridge admin React app. Enqueue.php enqueues the
 * built bundle and localizes the `flexaMailBridge` global before this renders.
 *
 * @package Flexa\MailBridge
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<div id="flexa-mailbridge-admin-root" class="flexa-mailbridge-wrap flexa-mailbridge-themed">
		<noscript><?php esc_html_e( 'Flexa MailBridge requires JavaScript to be enabled.', 'flexa-mailbridge' ); ?></noscript>
	</div>
</div>
