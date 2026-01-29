# Simple Fundraiser

A lightweight WordPress fundraising plugin for mosques and organizations.

## Features

- **Campaign Management** - Create unlimited fundraising campaigns
- **Goal Tracking** - Set targets with visual progress bars
- **Manual Donations** - Admin enters donations received via transfer
- **Spreadsheet View** - Inline editing with bulk actions
- **QRIS Support** - Upload QR code for Indonesian mobile payments
- **Donor Info** - Track name, email, phone, message, anonymous option
- **CSV Export** - Download donation data filtered by campaign/date
- **Dashboard Widget** - Quick stats overview

## Installation

1. Upload `simple-fundraiser` folder to `/wp-content/plugins/`
2. Activate via **Plugins** menu in WordPress
3. Go to **Fundraiser > Add Campaign** to create your first campaign

## Usage

### Creating a Campaign

1. Go to **Fundraiser > Add Campaign**
2. Enter title and description
3. Set goal amount and deadline
4. Add bank transfer info and/or upload QRIS image
5. Set featured image
6. Publish

### Adding Donations

1. Go to **Fundraiser > Donations > Add Donation**
2. Select the campaign
3. Enter amount and donor info
4. Save

### Viewing Campaigns

Visit `/campaign/` on your site to see all campaigns, or link to individual campaign URLs.

### Exporting Data

Go to **Fundraiser > Export** to download CSV of donations.

### Using Spreadsheet View

1. Go to **Fundraiser > Spreadsheet**
2. Filter by campaign (required for adding/editing types)
3. Click on any cell to edit inline
4. Use checkboxes for bulk selection
5. Apply bulk actions: Delete, Set Anonymous, or Change Type

## Changelog

### 1.5.0
- **New Feature:** Donations Spreadsheet view for inline editing
- **New Feature:** Bulk selection with Select All checkbox
- **New Feature:** Bulk actions (Delete, Set Anonymous, Change Type)
- **New Feature:** Type dropdown populated from campaign's donation types
- **New Feature:** Pagination (50 donations per page)
- **Improvement:** Mobile responsive spreadsheet design
- **Improvement:** Admin menu reordered (Stats at top, Settings at bottom)
- **Update:** Indonesian translations for spreadsheet feature

### 1.4.1
- Fix: Plugin submission compatibility improvements
- Fix: Translation text domain fixes

### 1.4.0
- New Feature: Hero Spotlight Gutenberg block
- New Feature: Campaign widget for Elementor
- Improvement: Carousel navigation option

### 1.3.0
- New Feature: Excel (.xlsx) Import and Export support
- New Feature: "Show/Hide Donations" toggle
- Improvement: Added social icons and rebranded Twitter to X
- Improvement: UI/UX enhancements (Typography, Spacing)
- Update: Indonesian translations

### 1.2.0
- New Feature: Bulk Import Donations
- New Feature: AJAX Pagination/Sorting

### 1.0.0
- Initial release

## License

GPL v2 or later
