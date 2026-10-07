# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the module uses
[semantic versioning](https://semver.org/).

## [2.0.0] - 2026-10-07

First open-source release, published under the MIT license.

### Added

- English alongside Polish: every string comes from the translation catalogues, the mail template
  ships in both languages, and the module's own domains hold the strings rather than the theme's.
- MIT headers, `LICENSE`, `README`, `SECURITY`, `CONTRIBUTING`, `composer.json`, `config.xml` and
  `index.php` guards in every directory.

### Security

- The submission endpoint checks a per-session token, carries a honeypot field, and limits both the
  browser and the submitting address: one request per 20 seconds per session and at most ten per
  hour per address, counted against the stored rows.
- The page address a submission carries is kept only when it points inside the shop, so the link on
  the configuration page is always an ordinary shop page.
- Rows are written with `Db::insert`, and every value shown in the back office is escaped.
