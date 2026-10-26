# Contact widget for PrestaShop 8 & 9

**Most visitors who want to talk to you will not write an e-mail and will not fill in a contact form — but they will leave a phone number if it takes one tap.**

> **Meta description (155 chars):** Floating contact widget for PrestaShop: phone, e-mail, opening hours and a callback form with consent. Free, MIT licensed.

---

## Why the unasked question matters for your store

A B2B buyer with a question about compatibility, a delivery date or a bulk price has a decision to make: write to you and wait, or close the tab and ask somebody who answers the phone. Most pick the second, and you never learn the question existed.

A contact form does not fix this, because it asks for the effort up front: a subject, a message, sometimes a name and a company. The visitor has to compose something. What they are actually willing to give you is a phone number and ten seconds.

That asymmetry is the whole point of the widget. You are not collecting enquiries, you are collecting permission to call.

- **Two fields, one tap** — a number or an e-mail address, and a consent checkbox.
- **You call, they do not have to explain** — the conversation starts on the phone, where it is short.
- **Notification where you work** — each submission lands in your inbox and in a list in the back office.

## What the module does

A button sits in the corner of the shop. Clicking it opens a panel with your heading and a short text, your phone number, e-mail address and opening hours, and a "we will call you back" form: a phone/e-mail switch, one field and a consent checkbox linking to your privacy policy. A submission is stored, listed in **Customers → Contact widget requests** and e-mailed to the address you choose. The widget is left out of the cart and the checkout; it adds nothing else to the page and touches no other module.

### Key features

- **Your contact details in one place** — heading, text, phone, e-mail and opening hours, edited per language in the configuration; an empty field hides its row.
- **Phone or e-mail** — the visitor picks; the field switches its type, label and keyboard, phone numbers are normalised, e-mail addresses validated.
- **Consent, with your own policy** — the checkbox is required and links to the CMS page you select.
- **Abuse guards** — a token tied to the visitor's own cookie, a honeypot field, and limits per browser, per address and per shop.
- **Cache-friendly** — the page markup is the same for every visitor; the form token is fetched when the visitor sends the form, so full-page caches can keep the page.
- **Submissions in the back office** — a list under Customers with filters, sorting, CSV export, `tel:`/`mailto:` links, and deleting per row or in bulk.
- **Speech bubble** — an optional short message next to the button, shown once per browser after the visitor's first scroll.
- **Accessible** — the panel is a labelled dialog that takes focus on open, closes on Esc or a click outside and returns focus to the button; errors are announced.
- **Retention you choose** — submissions older than the number of days you set are deleted once a day.
- **Per shop in multistore** — every shop keeps its own list and its own settings.
- **Its own icons** — Font Awesome Free icons inlined as SVG, so no icon font has to be present in the theme.
- **Theme styling** — the module stylesheet can be switched off when the theme styles the widget itself.
- **Optional notifications** — one e-mail per submission, or none if you prefer the list.

### What it stores, and what that means for GDPR

One row per submission: the channel, the contact the visitor typed, the consent flag, the page they were on, their IP address and the time. Nothing else, and nothing about customers who do not submit. The consent checkbox is required, so no row exists without it, and the text beside it links to your own policy.

Three ways to get the data out again, so a request to be forgotten does not need database access:

- **The delete button** next to every row in the back-office list, and bulk delete.
- **The retention setting** — submissions older than the number of days you choose are deleted once a day, by default after a year. Set it to 0 to keep them until you delete them yourself.
- **PrestaShop's own GDPR module** — with `psgdpr` installed, a customer's submissions are part of the data it exports and of the data it erases, matched on the e-mail address they left.

The rows live in one table that the module creates on install and drops on uninstall, so uninstalling deletes everything as well. There is no hidden copy anywhere else.

## Compatibility

| | |
|---|---|
| PrestaShop | 1.7.6 – 9.x |
| PHP | 7.4+ |
| Requirements | none — no external service, no captcha key, no icon font |
| Multistore | Supported — each shop has its own submissions, its own list and its own settings; a deleted shop takes its submissions with it |
| Themes | Renders in `displayBeforeBodyClosingTag`; the widget positions itself and needs no theme changes |

The module overrides no core class. It creates one table, one back-office tab and its settings, and removes all three when uninstalled. With `psgdpr` installed it answers that module's export and erasure hooks.

## Installation

1. Upload and install the module from **Modules → Module Manager**.
2. Open its configuration and check the heading, text, phone, e-mail and opening hours. Phone and e-mail start from your shop's contact details.
3. Pick the CMS page holding your privacy policy.
4. Set the address that should be notified, or leave it empty to rely on the list.
5. Decide how long submissions are kept — a year by default.

The privacy policy page is guessed on install from your CMS pages, so the link usually works straight away — check that it points where you expect.

## Configuration options

| Setting | Description |
|---|---|
| **Heading** | Panel heading, per language. |
| **Text under the heading** | One or two sentences under the heading, per language. |
| **Phone** | Shown as written; the link dials the digits. Empty hides the row. |
| **E-mail** | Shown as a `mailto:` link. Empty hides the row. |
| **Opening hours** | Free text per language, e.g. "Mon–Fri 9:00–17:00". Empty hides the row. |
| **Speech bubble** | Short message next to the button, shown once per visitor. Empty turns it off. |
| **Privacy policy page** | The CMS page the consent text links to. Pick "None" to hide the link. |
| **Notification e-mail** | Where each submission is e-mailed. Empty means no e-mail; submissions still appear in the list. |
| **Keep submissions for** | Days a submission is kept before it is deleted automatically. 0 keeps everything until you delete it yourself. |
| **Load the module stylesheet** | On by default. Turn it off when your theme styles the widget; the theme then also hides the honeypot field (`.m4p-contact-widget__hp`). |

In multistore the settings belong to the shop selected in the selector, so one shop can notify a different address than another.

## Frequently asked questions

**Does it need a captcha key or an external service?**
No. The guards are a token tied to the visitor's own cookie, a hidden honeypot field and rate limits counted in your own database.

**Can someone flood it?**
One submission per 20 seconds per browser, ten per hour per address and a hundred per hour per shop. The per-shop figure is the backstop: behind a proxy the shop sees the address the request claims to come from, so a limit counted per address alone would not hold on its own.

**Someone asks me to delete their data. Where is it?**
In **Customers → Contact widget requests** — delete the row there. With `psgdpr` installed, its own erasure request covers this module too, and the retention setting clears old submissions without anyone asking.

**What happens to submissions when I uninstall?**
They are deleted with the module's table. Export them first if you need them.

**Can I change where the button sits?**
It is positioned in CSS, in `views/css/widget.css` — override the `.m4p-contact-widget` rules from your theme, or switch the module stylesheet off and style the widget entirely in the theme.

**Is it shown on every page?**
On every front-office page except the cart, the checkout and the order confirmation, where the buyer should not be pulled away from finishing the order.

---

**Keywords:** prestashop contact widget, prestashop callback widget, prestashop request a call, prestashop contact button, prestashop lead capture, floating contact form prestashop

## Third-party assets

The icons in `views/img` are [Font Awesome Free](https://fontawesome.com) 6.7.2 (CC BY 4.0); each file keeps its licence comment.

## License

MIT — see [LICENSE](LICENSE). Free to use commercially, fork and modify; keep the copyright notice.

## Contributing

Bug reports and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). For security
issues, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

---

Built by [Nice Code](https://nice-code.com/pl/oferta/moduly-prestashop) — we build and maintain PrestaShop stores.

© Nice Code sp. z o.o. (Modules4Presta) — released under the MIT license.
