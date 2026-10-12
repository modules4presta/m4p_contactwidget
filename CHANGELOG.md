# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the module uses
[semantic versioning](https://semver.org/).

## [2.1.0] - 2026-10-12

### Added

- A retention setting: submissions older than the number of days chosen in the configuration are
  deleted once a day, after a year by default; 0 keeps them until someone deletes them.
- A delete button next to every submission in the back-office list.
- `actionExportGDPRData` and `actionDeleteGDPRCustomer`, so the `psgdpr` module exports and erases
  the submissions tied to a customer's e-mail address along with the rest of their data.
- Multistore: the list shows the submissions of the shops the selector covers, with a shop column,
  and the privacy page, the notification address and the retention period belong to the shop
  selected above the form.
- `actionObjectShopDeleteAfter`, so a deleted shop takes its own submissions with it.
- A cap of a hundred submissions per shop per hour beside the per-address cap, which holds whatever
  address a request claims to come from.
- Indexes `ip_date` and `shop_date` behind the rate checks, the list and the purge; the upgrade
  script adds them to a table that already exists.

### Changed

- The form token is derived from a secret kept in the visitor's own cookie, so it belongs to one
  browser session.
- Every message the widget shows comes from the translation catalogues through `Media::addJsDef`;
  the JavaScript carries no text of its own.
- The button draws its two icons as inline SVG, so the module needs no icon font from the theme.
- The notification e-mail goes out in the shop's default language, falling back to a language the
  module ships a template for.

## [2.0.0] - 2026-10-07

First open-source release, published under the MIT license.

### Added

- English alongside Polish: every string comes from the translation catalogues, the mail template
  ships in both languages, and the module's own domains hold the strings rather than the theme's.
- MIT headers, `LICENSE`, `README`, `SECURITY`, `CONTRIBUTING`, `composer.json`, `config.xml` and
  `index.php` guards in every directory.

### Security

- The submission endpoint checks a submission token, carries a honeypot field, and limits both the
  browser and the submitting address: one request per 20 seconds per session and at most ten per
  hour per address, counted against the stored rows.
- The page address a submission carries is kept only when it points inside the shop, so the link on
  the configuration page is always an ordinary shop page.
- Rows are written with `Db::insert`, and every value shown in the back office is escaped.
