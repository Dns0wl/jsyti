=== HW Invoice PDF ===
Contributors: hayuwidyas
Requires at least: 6.2
Tested up to: 6.4
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate professional PDF invoices for WooCommerce orders with an optimized Dompdf engine and modern admin design dashboard.

== Description ==
HW Invoice PDF provides secure PDF invoices for every WooCommerce order (old and new). Key features include:

* Download buttons on order list and single order admin screens.
* Customizable invoice design (logo, background color/image, fonts, accent color, footer text).
* Dompdf-powered PDF streaming only when needed for optimal performance.
* Automatic invoice IDs for every new order plus a monthly WP-Cron job to backfill legacy orders (with manual trigger support).
* Preview button to test invoice output using latest or sample orders.

== Installation ==
1. Upload the `hw-invoice-pdf` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce → HW Invoice PDF to configure your invoice branding and run backfill if needed.

== Changelog ==
= 1.0.0 =
* Initial release.
