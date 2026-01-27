=== Simple Fundraiser ===
Contributors: nurulishlah
Tags: fundraising, donation, charity, mosque, infaq
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple fundraising plugin for mosques and organizations to manage donations and campaigns.

== Description ==

Simple Fundraiser is a lightweight plugin designed to help mosques, charities, and non-profit organizations manage fundraising campaigns easily. 

**Features:**
*   **Unlimited Campaigns:** Create as many fundraising campaigns as you need.
*   **Manual Donation Entry:** Record offline donations (cash, transfer) manually via the admin dashboard.
*   **Import Donations:** Bulk import donations via CSV file with smart delimiter detection.
*   **Progress Bars:** Display beautiful progress bars for each campaign using shortcodes or built-in templates.
*   **Donation Breakdown:** Support for sub-categories (e.g., Sembako, Activities) with automatic totals.
*   **Multi-Currency Support:** Customizable currency symbol, decimal places, and separators.
*   **Payment Information:** Display Bank Transfer details and QRIS codes.
*   **WhatsApp Confirmation:** Allow donors to quickly confirm their donation via WhatsApp.
*   **Export Data:** Export donation records to CSV or Excel (.xlsx) for reporting.
*   **Translation Ready:** Fully localized and includes Bahasa Indonesia translation.
*   **Excel Support:** Import and Export data using Excel files.
*   **Social Sharing:** Integrated sharing buttons for WhatsApp, Facebook, and X.

== Installation ==

1.  Upload the `simple-fundraiser` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Fundraiser > Settings** to configure your currency and payment information.
4.  Create your first campaign under **Fundraiser > Add Campaign**.

== Frequently Asked Questions ==

= Can I accept online payments automatically? =
Currently, this plugin is designed for manual donation recording (offline donations, transfers confirmed manually). Automatic payment gateway integration is planned for future versions.

= How do I display a campaign? =
The plugin automatically creates a page for each campaign. You can also view all campaigns at `yourdomain.com/sf_campaign`.

= Can I change the currency? =
Yes! Go to **Fundraiser > Settings** to change the currency symbol (e.g., Rp, $) and formatting.

== Screenshots ==

1.  **Fundraiser Dashboard** - Overview of your active campaigns and total raised.
2.  **Campaign Editor** - Easy-to-use interface to set goals, deadlines, and donation types.
3.  **Frontend Campaign** - Beautiful progress bar and donation list.
4.  **Donation Entry** - Simple form to record new donations.

== Changelog ==

= 1.3.0 =
*   New Feature: Excel (.xlsx) Import and Export support.
*   New Feature: "Show/Hide Donations" toggle for better UX.
*   Improvement: Added icons to confirmation and share buttons.
*   Improvement: Renamed "Twitter" to "X".
*   Improvement: Enhanced typography and layout spacing.
*   Update: Complete Indonesian (id_ID) translation.

= 1.2.0 =
*   New Feature: Bulk Import Donations via CSV.
*   New Feature: AJAX-based pagination and sorting for donation lists.
*   Improvement: Added loading state and smoother UI interactions.

= 1.1.0 =
*   New Feature: Multi-category donation types (e.g., Sembako, Activity) with frontend breakdown.
*   Improvement: Enhanced frontend styling with refined layout and visuals.
*   Update: Added Bahasa Indonesia translation updates.

= 1.0.0 =
*   Initial release.
