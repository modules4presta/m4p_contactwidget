# Contact widget for PrestaShop 8 & 9

**Most visitors who want to talk to you will not write an e-mail and will not fill in a contact form – but they will leave a phone number if it takes one tap.**

> **Meta description (155 chars):** Floating contact widget for PrestaShop: phone, e-mail, opening hours and a callback form with consent. Free, MIT licensed.

---

## Why the unasked question matters for your store

A B2B buyer with a question about compatibility, a delivery date or a bulk price has a decision to make: write to you and wait, or close the tab and ask somebody who answers the phone. Most pick the second, and you never learn the question existed.

A contact form does not fix this, because it asks for the effort up front: a subject, a message, sometimes a name and a company. The visitor has to compose something. What they are actually willing to give you is a phone number and ten seconds.

That asymmetry is the whole point of the widget. You are not collecting enquiries, you are collecting permission to call.

- **Two fields, one tap** – a number or an e-mail address, and a consent checkbox.
- **You call, they do not have to explain** – the conversation starts on the phone, where it is short.
- **Notification where you work** – each submission lands in your inbox and in a list in the back office.

## What the module does

A labelled button sits in the corner of the shop and says whether your desk is open now ("open until 16:00", "tomorrow from 7:30"). Clicking it opens a panel with your heading and a short text, your phone number, e-mail address and opening hours, and a "we will call you back" form: a phone/e-mail switch, one field and a consent checkbox linking to your privacy policy. A submission is stored, listed in **Customers → Contact widget requests** and e-mailed to the address you choose. By default the widget is left out of the cart and the checkout, and the list of page types without it is yours to change; it adds nothing else to the page and touches no other module.

### Key features

- **Your contact details in one place** – heading, text, phone, e-mail and opening hours, edited per language in the configuration; an empty field hides its row.
- **Open or closed, live** – opening hours per weekday plus dated exceptions (holidays); the button (unless switched off) and the panel show the status in the shop's time zone and keep it current every minute, and the note under the form changes with it.
- **Out of the way on phones** – a square button that respects the safe area of the screen, a panel opening as a sheet from the bottom, a distance setting for bars fixed to the bottom, and a list of elements (slider controls, cookie banner) the button steps aside for while it would cover them.
- **Phone or e-mail** – the visitor picks; the field switches its type, label and keyboard, phone numbers are normalised, e-mail addresses validated.
- **Consent, with your own policy** – the checkbox is required; its text, the link text and the target (a CMS page or any address) are set per language.
- **Channels you choose** – the phone row, the e-mail row and each option of the form (call-back by phone, reply by e-mail) have their own switch.
- **Abuse guards** – a token tied to the visitor's own cookie, a honeypot field, and limits per browser, per address and per shop.
- **Cache-friendly** – the page markup is the same for every visitor; the form token is fetched when the visitor sends the form, so full-page caches can keep the page.
- **Submissions in the back office** – a list under Customers with filters, sorting, CSV export, `tel:`/`mailto:` links, and deleting per row or in bulk.
- **Speech bubble** – an optional short message next to the button, shown once per visit after the visitor scrolls past the first screen.
- **Accessible** – the button names the desk status, the panel is a labelled dialog that takes focus on open, keeps the keyboard inside, closes on Esc or a click outside and returns focus to the button; errors are announced.
- **Retention you choose** – submissions older than the number of days you set are deleted once a day.
- **Per shop in multistore** – every shop keeps its own list and its own settings.
- **Its own icons** – Lucide icons inlined as SVG, so no icon font has to be present in the theme.
- **Theme styling** – colours, radii, shadow and fonts come from `--m4p-cw-*` CSS variables a theme sets in its own stylesheet; the module stylesheet can also be switched off when the theme styles the widget itself.
- **Optional notifications** – one e-mail per submission, or none if you prefer the list.
- **Ready for measurement** – `data-cta-*` attributes say where the widget is, why it is on screen and which channel the visitor used, so an analytics script reads them without code of its own.

### What it stores, and what that means for GDPR

One row per submission: the channel, the contact the visitor typed, the consent flag, the page they were on, their IP address and the time. Nothing else, and nothing about customers who do not submit. The consent checkbox is required, so no row exists without it, and the text beside it links to your own policy.

Three ways to get the data out again, so a request to be forgotten does not need database access:

- **The delete button** next to every row in the back-office list, and bulk delete.
- **The retention setting** – submissions older than the number of days you choose are deleted once a day, by default after a year. Set it to 0 to keep them until you delete them yourself.
- **PrestaShop's own GDPR module** – with `psgdpr` installed, a customer's submissions are part of the data it exports and of the data it erases, matched on the e-mail address they left.

The rows live in one table that the module creates on install and drops on uninstall, so uninstalling deletes everything as well. There is no hidden copy anywhere else.

## Compatibility

| | |
|---|---|
| PrestaShop | 1.7.6 – 9.x |
| PHP | 7.4+ |
| Requirements | none – no external service, no captcha key, no icon font; with [`m4p_core`](https://github.com/modules4presta/m4p_core) enabled, the phone and the weekly hours of its sales department are used when set there |
| Multistore | Supported – each shop has its own submissions, its own list and its own settings; a deleted shop takes its submissions with it |
| Themes | Renders in `displayBeforeBodyClosingTag`; the widget positions itself and needs no theme changes |

The module overrides no core class. It creates one table, one back-office tab and its settings, and removes all three when uninstalled. With `psgdpr` installed it answers that module's export and erasure hooks.

## Installation

1. Upload and install the module from **Modules → Module Manager**.
2. Open its configuration and check the heading, text, phone, e-mail and opening hours. Phone and e-mail start from your shop's contact details.
3. Pick the CMS page holding your privacy policy.
4. Set the address that should be notified, or leave it empty to rely on the list.
5. Decide how long submissions are kept – a year by default.

The privacy policy page is guessed on install from your CMS pages, so the link usually works straight away – check that it points where you expect.

## Configuration options

| Setting | Description |
|---|---|
| **Button label** | Text on the button, per language, e.g. "Contact". |
| **Show the hours on the button** | On by default: open now or the next opening time next to the label, with the status dot. Off: the label alone. |
| **Heading** | Panel heading, per language. |
| **Text under the heading** | One or two sentences under the heading, per language. |
| **Show the phone / Show the e-mail** | Switch each row on or off without clearing the number or the address. |
| **Phone** | Shown as written; the link dials the digits. Empty hides the row. With `m4p_core` enabled, the phone of its sales department takes precedence when set. |
| **E-mail** | Shown as a `mailto:` link. Empty hides the row. |
| **Hide the widget on** | Page types without the widget: home, categories, products, CMS, search, brands, listings, contact, cart, checkout, order confirmation, log-in pages, customer account, other pages (e.g. blog). Cart, checkout and order confirmation by default. |
| **Opening hours text** | Free text per language, e.g. "Mon–Fri 9:00–17:00", shown only while no weekly hours are set. Empty hides the row. |
| **Speech bubble** | Short message next to the button, shown once per visit after the visitor scrolls past the first screen. Empty turns it off. |
| **Call-back by phone / Reply by e-mail** | The options of the form. With one on, the switch between them is hidden; with both off, the form is hidden. |
| **Form heading and button, for a call-back and for an e-mail reply** | Per language; they follow the option the visitor picks. |
| **Call-back note during / outside opening hours** | Line under the form heading, per language, following the desk status. |
| **Consent text** | Text next to the required checkbox, per language. |
| **Privacy policy link text** | Link after the consent text, per language. Empty hides the link. |
| **Privacy policy page** | The CMS page the link leads to. Pick "None" to hide the link. |
| **Privacy policy address** | Per language, for a policy outside the shop; when filled in it is used instead of the page. |
| **Message after sending** | Shown in the panel once the request is saved, per language. |
| **Notification e-mail** | Where each submission is e-mailed. Empty means no e-mail; submissions still appear in the list. |
| **Keep submissions for** | Days a submission is kept before it is deleted automatically. 0 keeps everything until you delete it yourself. |
| **Distance from the bottom on phones** | Lifts the button above a bar fixed to the bottom of the screen, 0–200 px. A theme can set `--m4p-contact-offset` on a page instead. |
| **Elements the button does not cover** | CSS selectors, comma-separated. While the button would cover one of them it hides, until the page is scrolled. |
| **Opening hours: Monday … Sunday** | From–to, e.g. `7:30-16:00`; empty means closed. With every day empty the button shows no status. With `m4p_core` enabled, the hours of its sales department take precedence when set. |
| **Opening hours: exceptions** | One date per line: `2026-12-24 8:00-12:00` or `2026-12-25 closed`. The panel lists those in the next two weeks. |
| **Load the module stylesheet** | On by default. Turn it off when your theme styles the widget; the theme then also hides the honeypot field (`.m4p-contact-widget__hp`). |

In multistore the settings belong to the shop selected in the selector, so one shop can notify a different address than another.

## Frequently asked questions

**Does it need a captcha key or an external service?**
No. The guards are a token tied to the visitor's own cookie, a hidden honeypot field and rate limits counted in your own database.

**Can someone flood it?**
One submission per 20 seconds per browser, ten per hour per address and a hundred per hour per shop. The per-shop figure is the backstop: behind a proxy the shop sees the address the request claims to come from, so a limit counted per address alone would not hold on its own.

**Someone asks me to delete their data. Where is it?**
In **Customers → Contact widget requests** – delete the row there. With `psgdpr` installed, its own erasure request covers this module too, and the retention setting clears old submissions without anyone asking.

**What happens to submissions when I uninstall?**
They are deleted with the module's table. Export them first if you need them.

**Can I change where the button sits?**
It is positioned in CSS, in `views/css/widget.css` – override the `.m4p-contact-widget` rules from your theme, or switch the module stylesheet off and style the widget entirely in the theme.

**Can I measure how the widget performs?**
The markup carries attributes an analytics script can read, and a sent form is passed to `window.m4pTrack('contact_request', { channel })` when a module provides that function.

| Attribute | Element | Value |
|---|---|---|
| `data-cta-placement`, `data-cta-kind` | widget root | `widget` |
| `data-cta-trigger` | widget root | `scroll` when the speech bubble appears, `click` when the visitor opens the panel first, `static` when no bubble is shown |
| `data-cta-channel` | phone and e-mail links | `phone`, `email` |
| `data-cta-dismiss` | close buttons of the panel and the bubble | – |

**Is it shown on every page?**
On every front-office page except the page types ticked under **Hide the widget on** – by default the cart, the checkout and the order confirmation, where the buyer should not be pulled away from finishing the order.

---

**Keywords:** prestashop contact widget, prestashop callback widget, prestashop request a call, prestashop contact button, prestashop lead capture, floating contact form prestashop

## Third-party assets

The icons in `views/img` are [Lucide](https://lucide.dev) icons (ISC licence), with the licence in `views/img/LICENSE-lucide.txt`.

## License

MIT – see [LICENSE](LICENSE). Free to use commercially, fork and modify; keep the copyright notice.

## Contributing

Bug reports and pull requests are welcome – see [CONTRIBUTING.md](CONTRIBUTING.md). For security
issues, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

---

Built by [Nice Code](https://nice-code.com/pl/oferta/moduly-prestashop) – we build and maintain PrestaShop stores.

© Nice Code sp. z o.o. (Modules4Presta) – released under the MIT license.
