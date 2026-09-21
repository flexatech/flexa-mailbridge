=== Flexa MailBridge ===
Contributors: flexatech
Tags: smtp, wp mail smtp, email log, email tracking, email delivery
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An email delivery layer for WordPress: route wp_mail() through any SMTP or API mailer, with fallback, queue, logs, tracking, and reports.

== Description ==

Flexa MailBridge is the email delivery layer for your WordPress site. It sits between `wp_mail()` and your mail provider, so every message your site sends (order emails, password resets, form notifications, newsletters) goes out through a real mailer instead of the default `mail()` that so often lands in spam.

Around that core it adds the parts a dependable delivery pipeline needs: a fallback mailer for when the primary one fails, an optional background queue that retries temporary failures, a full log of everything sent, open and click tracking, and delivery reports.

= Dashboard =

The landing screen pulls everything together in one view: whether WordPress can send email right now, the last few days of delivery stats (sent, failed, open rate, click rate), an activity chart, your most recent sends, and the top reasons messages fail. Each panel links straight to the matching tab when you want the detail.

= Routing and fallback =

Set the From name and address (with optional force overrides), pick a primary mailer, and choose a fallback that takes over automatically if the primary fails. Eighteen transports are supported:

* Custom SMTP (any host)
* SendGrid, Mailgun, Brevo (Sendinblue), Postmark, Mailjet, SparkPost, SMTP.com, SendPulse, Pepipost, Mandrill, Amazon SES, Yournotify
* IONOS
* Gmail / Google Workspace, Outlook / Microsoft 365, and Zoho Mail (OAuth: connect with a click, no password stored)
* WordPress default (`mail()`)

API credentials and OAuth tokens are encrypted at rest (AES-256-GCM) and are never returned to the browser in plain text.

= Delivery queue and retries =

Turn on the optional queue to send email in the background instead of during the page request, so a slow provider never holds up checkout or a form submission. Temporary failures (rate limits, timeouts, transient provider errors) are retried with backoff up to a limit you set; permanent errors are left alone rather than retried forever. The queue is off by default, so nothing changes until you enable it.

= Email logs =

Every message is logged with its status (sent / failed / pending), mailer, recipients, subject, source, and the exact error when a send fails. Browse, search, and filter the log, open any entry to see its full body and engagement, and export the current view to CSV. Set a retention window to prune old logs automatically.

= Open and click tracking =

Optionally embed a tracking pixel and rewrite links so you can see which emails were opened and which links were clicked, per message.

= Reports =

A dashboard widget and a Reports tab summarise delivery trends (sent vs. failed, open and click rates, busiest mailers, top links) over 7/30/90 days, with optional weekly and monthly digest emails.

= WP-CLI =

* `wp flexa-mailbridge log list`: list and filter the email log (status, mailer, search, date range; table/csv/json output)
* `wp flexa-mailbridge log get <id>`: show one entry in full
* `wp flexa-mailbridge test <to>`: send a test email through the active mailer
* `wp flexa-mailbridge reset`: wipe all Flexa MailBridge data

= Import =

Migrate settings and logs from WP Mail SMTP, Easy WP SMTP, SMTP Mailer, WP SMTP, or WP Mail Bank. Secrets are re-encrypted on import.

== External services ==

Flexa MailBridge does not contact any external service on its own. It connects to a mail provider **only when you select and configure that provider as your mailer** (or connect it via OAuth). Each provider below is contacted solely to deliver the WordPress email you send. In every case the request happens when an email is sent, and the data transmitted is the outgoing message (recipients, subject, body, headers, and any attachments) together with the credentials you entered for that provider (sent as an authorization header). No data is sent to the plugin author, and no analytics or telemetry is collected.

For the OAuth mailers (Gmail, Outlook, Zoho) there is an additional one-time authorization step: when you click "Connect", your browser is redirected to the provider's sign-in page, and the plugin then exchanges the returned authorization code for an access token over HTTPS. Tokens are stored encrypted and refreshed with the provider as needed.

The Custom SMTP and IONOS mailers connect over SMTP to the host you configure (for IONOS, one of `smtp.ionos.com` / `.de` / `.es` / `.fr` / `.co.uk`); no third-party HTTP API is involved.

API mailers (contacted at send time):

* **SendGrid**: `https://api.sendgrid.com`. Terms: https://www.twilio.com/en-us/legal/tos | Privacy: https://www.twilio.com/en-us/legal/privacy
* **Mailgun**: `https://api.mailgun.net` or `https://api.eu.mailgun.net`. Terms: https://www.mailgun.com/legal/terms/ | Privacy: https://www.mailgun.com/legal/privacy-policy/
* **Brevo**: `https://api.brevo.com`. Terms: https://www.brevo.com/legal/termsofuse/ | Privacy: https://www.brevo.com/legal/privacypolicy/
* **Postmark**: `https://api.postmarkapp.com`. Terms: https://postmarkapp.com/terms-of-service | Privacy: https://postmarkapp.com/privacy-policy
* **Mailjet**: `https://api.mailjet.com`. Terms: https://www.mailjet.com/legal/terms/ | Privacy: https://www.mailjet.com/legal/privacy-policy/
* **SparkPost** (now part of Bird): `https://api.sparkpost.com` or `https://api.eu.sparkpost.com`. Terms: https://bird.com/en-us/legal/terms | Privacy: https://bird.com/en-us/legal/privacy
* **SMTP.com**: `https://api.smtp.com`. Terms: https://www.smtp.com/policies/terms-of-use/ | Privacy: https://www.smtp.com/policies/privacy-policy/
* **SendPulse**: `https://api.sendpulse.com` (an OAuth token is fetched from the same host before sending). Terms: https://sendpulse.com/legal/terms | Privacy: https://sendpulse.com/legal/pp
* **Pepipost / Netcore**: `https://api.pepipost.com`. Terms: https://netcorecloud.com/terms-of-service/ | Privacy: https://netcorecloud.com/privacy-policy/
* **Mandrill**: `https://mandrillapp.com`. Terms: https://mailchimp.com/legal/terms/ | Privacy: https://mailchimp.com/legal/privacy/
* **Yournotify**: `https://api.yournotify.com`. Terms: https://yournotify.com/terms | Privacy: https://yournotify.com/privacy-policy/
* **Amazon SES**: `https://email.{region}.amazonaws.com`. Terms: https://aws.amazon.com/service-terms/ | Privacy: https://aws.amazon.com/privacy/

OAuth mailers (authorization + send):

* **Gmail / Google Workspace**: `https://accounts.google.com`, `https://oauth2.googleapis.com`, `https://gmail.googleapis.com`. Terms: https://policies.google.com/terms | Privacy: https://policies.google.com/privacy
* **Outlook / Microsoft 365**: `https://login.microsoftonline.com`, `https://graph.microsoft.com`. Terms: https://www.microsoft.com/servicesagreement | Privacy: https://privacy.microsoft.com/privacystatement
* **Zoho Mail**: `https://accounts.zoho.{region}` and `https://mail.zoho.{region}` (region one of com/eu/in/com.au/jp). Terms: https://www.zoho.com/terms.html | Privacy: https://www.zoho.com/privacy.html

= Deactivation feedback (Flexa Product Intelligence) =

When you go to deactivate Flexa MailBridge on the Plugins screen, a short optional survey asks why. It is served by Flexa's product intelligence service at https://product-intelligence.flexacommerce.com. It runs only on `wp-admin/plugins.php`, never on the front end, and never blocks deactivation.

What is sent, and when:

* On opening the Plugins screen: a request to `/api/v1/config` (product slug and tier) to load the survey configuration. Cached for 6 hours.
* When you deactivate or interact with the survey: the reason you pick and any optional message you type, sent to `/api/v1/deactivations`, `/api/v1/events`, `/api/v1/feedback`, `/api/v1/feature-requests`, `/api/v1/recovery-events`.

Every request includes an anonymous per-site identifier (a random UUID), the plugin version and tier, and by default your WordPress/PHP version and locale. No email, site domain, user identity or raw IP is collected. Turn environment off with `add_filter( 'flexa_mailbridge/deactivation_survey/config', fn( $c ) => array( 'collect_environment' => false ) + $c );` and disable the whole survey with `add_filter( 'flexa_mailbridge/deactivation_survey/enabled', '__return_false' );`.

Service terms and privacy policy: https://flexacommerce.com/pages/terms and https://flexacommerce.com/pages/privacy

== Installation ==

1. Upload the `flexa-mailbridge` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Flexa MailBridge** in the admin menu, choose your mailer, enter its credentials (or connect via OAuth), and send a test email.

== Source code for compiled JavaScript and CSS ==

The admin app ships as a compiled bundle in `assets/dist/`. The human-readable
TypeScript and CSS source, together with its build config, lives in the public
repository at https://github.com/flexatech/flexa-mailbridge/ (under `apps/admin/`) and
is built with pnpm + Vite from the plugin root:

1. `pnpm install`
2. `pnpm run build`   (or `pnpm run dev` for a watched dev build)

This runs a type check and writes the bundle to `assets/dist/`.

== Frequently Asked Questions ==

= Will sending email slow down my site? =

Not if you enable the delivery queue. With the queue on, `wp_mail()` hands the message off and returns right away, and the actual send happens in the background on WP-Cron, with automatic retries for temporary failures.

= Are my API keys and passwords safe? =

Yes. Every secret credential and OAuth token is encrypted with AES-256-GCM before it is stored, and secrets are masked (never sent in plain text) when the settings screen loads.

= Does tracking require logging? =

Yes. Open/click tracking is recorded against the email log, so email logging must be enabled for tracking to work.

= Can I use it with WP-CLI? =

Yes. See the WP-CLI commands listed in the description.

== Screenshots ==

1. Dashboard: email health, delivery stats, recent sends, and failure causes in one view.
2. Mailer settings: pick a sending service, set the From name and address, and send a test.
3. Delivery queue: send in the background and retry temporary failures.
4. Email logs: every message sent, with search, filter, and CSV export.
5. Tracking: measure opens and clicks per message.
6. Reports: sent, opens, clicks, and open rate over time, with weekly and monthly summaries.
7. Import: bring settings and logs over from another SMTP plugin.
8. Additional: developer options, including pausing real sending while still logging.
9. Danger Zone: reset all settings and drop the log tables for a clean start.

== Changelog ==

= 1.0.2 =
* New: a Dashboard landing screen that brings email health, recent delivery stats, an activity chart, recent sends, and the top failure causes into one view, each linking to its full tab. The former Overview is now "Health check".

= 1.0.1 =
* New: an optional deactivation feedback survey. If you deactivate the plugin, a short survey asks why, so we know what to improve. It is entirely optional, admin-only, and never blocks or delays deactivation. See the External services section for exactly what is sent and how to turn it off.

= 1.0.0 =
* First stable release.
* 18 mailers: custom SMTP, 13 API providers, 3 OAuth providers (Gmail/Outlook/Zoho), IONOS, and WordPress default, with a configurable fallback mailer.
* Optional background delivery queue with automatic retry of temporary failures.
* Email logging with search/filter, per-message detail, CSV export, and automatic retention.
* Open and click tracking.
* Delivery reports (dashboard widget, Reports tab, weekly/monthly digests).
* Import of settings and logs from five other SMTP plugins.
* WP-CLI: `log list`, `log get`, `test`, `reset`.
* All secrets encrypted at rest (AES-256-GCM); OAuth and tracking endpoints use HMAC-signed tokens.
