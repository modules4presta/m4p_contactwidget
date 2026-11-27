# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the module uses
[semantic versioning](https://semver.org/).

## [3.5.0] - 2026-11-25

### Added

- Switch "Show the hours on the button": with it off the floating button shows the label alone, without the
  status dot and the open now / next opening time. The upgrade turns it on.

### Changed

- The default call-back note outside opening hours reads "We will call you back as soon as we open." It is
  written on install and only where no note is stored yet.

## [3.4.0] - 2026-11-25

### Changed

- With the `m4p_core` module enabled, the phone and the weekly hours come from its sales department
  (`M4P_CORE_SALES_PHONE`, `M4P_CORE_SALES_HOURS`) when they are set there, so the header, the footer and the
  widget show the same data. The widget's own fields apply otherwise.

## [3.3.0] - 2026-11-19

### Added

- A switch for each channel: the phone row, the e-mail row, call-back by phone and reply by e-mail in the form.
  With one form option on, the switch between them is hidden; with both off, the form is hidden.
- Page types without the widget, chosen in the configuration; cart, checkout and order confirmation by default.
- Per-language form headings and buttons for both options, the consent text, the privacy link text and the
  message after sending.
- Privacy policy address per language, used instead of the CMS page for a policy outside the shop.

### Changed

- The configuration is split into the widget, the call-back form, opening hours, and notifications and display.
- A wrong phone number, e-mail address or missing consent shows as a field error with an icon under that control,
  tied to it with `aria-invalid` and `aria-describedby`; a sending error and the thank-you text show as message
  blocks with an icon. With a theme's `m4p-msg` / `m4p-field-error` components they take the theme's look;
  otherwise the module stylesheet draws them, and `--m4p-cw-error-wash` sets the error block background.
- The upgrade keeps the widget as it was: every channel on, hidden in the cart and the checkout, the texts it
  showed so far.

## [3.2.0] - 2026-11-19

### Added

- Opening hours per weekday (from–to or closed) and dated exceptions, with the desk status computed in the shop's
  time zone: "open until 16:00", "closed, we open tomorrow at 7:30". The button and the panel show it, the script
  keeps it current every minute, and the panel lists the week and the exceptions of the next two weeks.
- Button label per language; the button shows the label and the status, on phones a square button with a status dot.
- Call-back note under the form heading, per language, one for opening hours and one for outside them.
- On phones: the panel opens as a sheet from the bottom, the button respects the safe area, a distance setting lifts
  it above bars fixed to the bottom (`--m4p-contact-offset` for themes), and a list of CSS selectors it steps aside
  for while it would cover them.
- The speech bubble shows once per visit, after the visitor scrolls past the first screen, so it never covers the
  page opening.
- `--m4p-cw-*` CSS variables for the colours, radii, shadow and display font, read at zero specificity so a theme's
  own values win.
- Keyboard focus stays in the open panel; the submit button names the chosen channel.

### Changed

- The panel shows the phone first in large type, channels with arrows, the call-back form on a tinted ground with
  the input and the button in one row. Themes overriding `widget.tpl` need to follow the new markup.
- Icons are Lucide (ISC), drawn as strokes; the button carries a phone icon.
- The opening hours text shows while no weekly hours are set.

## [3.1.0] - 2026-11-12

### Added

- `data-cta-placement` and `data-cta-kind` on the widget root, `data-cta-channel` on the phone and
  e-mail links and `data-cta-dismiss` on the close buttons of the panel and the speech bubble.
- `data-cta-trigger` on the widget root, set when the widget gives a reason to be noticed: `scroll`
  when the speech bubble appears, `click` when the visitor opens the panel first, `static` when no
  bubble is shown.

## [3.0.0] - 2026-10-21

### Added

- Phone, e-mail and opening hours in the panel, edited in the configuration together with the heading,
  the text under it and the speech bubble (per language). Phone and e-mail start from the shop's
  contact details; an empty field hides its row.
- Customers > Contact widget requests (`AdminM4pContactRequests`): submissions with filters, sorting,
  CSV export, `tel:`/`mailto:` links and deleting per row or in bulk, limited to the shops the
  multistore selector covers, with a shop column in multistore.
- A switch that skips the module stylesheet when the theme styles the widget.
- Font Awesome Free icons as inline SVG (`views/img`).
- The submission event is passed to `window.m4pTrack` when an analytics module provides it.

### Changed

- The widget renders in `displayBeforeBodyClosingTag` and is left out of the cart, the checkout and
  the order confirmation; its CSS and JavaScript load on the same pages only.
- New markup: the panel is a labelled dialog whose heading takes focus on open, Esc and a click
  outside close it, the phone / e-mail choice is a radio group that switches the field's type, label
  and keyboard, and the thank-you message replaces the form. Themes overriding `widget.tpl` or styling
  the 2.x class names need to follow the new markup.
- The form token is fetched from the submit endpoint when the form is sent, so the page markup is the
  same for every visitor and full-page caches can keep it.
- Messages for the script come from `data-*` attributes on the widget root.
- The speech bubble waits for the visitor's first scroll and shows once per browser.
- Old submissions are purged when a submission arrives and when the back-office list opens.
- The upgrade keeps every stored setting and only writes the ones that are missing.

### Removed

- The submission list on the configuration page, replaced by the Customers tab.

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
