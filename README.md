# Callback request widget for PrestaShop 8 & 9

**Most visitors who want to talk to you will not write an e-mail and will not fill in a contact form — but they will leave a phone number if it takes one tap.**

> **Meta description (155 chars):** A floating callback request widget for PrestaShop. Visitors leave a phone or e-mail, you get notified. Consent built in. Free, MIT licensed.

---

## Why the unasked question matters for your store

A B2B buyer with a question about compatibility, a delivery date or a bulk price has a decision to make: write to you and wait, or close the tab and ask somebody who answers the phone. Most pick the second, and you never learn the question existed.

A contact form does not fix this, because it asks for the effort up front: a subject, a message, sometimes a name and a company. The visitor has to compose something. What they are actually willing to give you is a phone number and ten seconds.

That asymmetry is the whole point of the widget. You are not collecting enquiries, you are collecting permission to call.

- **Two fields, one tap** — a number or an e-mail address, and a consent checkbox.
- **You call, they do not have to explain** — the conversation starts on the phone, where it is short.
- **Notification where you work** — each submission lands in your inbox and in a list in the back office.

## What the module does

A button sits in the corner of the shop. Clicking it opens a small panel with a phone/e-mail switch, one field and a consent checkbox linking to your privacy policy. A submission is stored, shown in the module's configuration page and e-mailed to the address you choose. It adds nothing else to the page and touches no other module.

### Key features

- **Phone or e-mail** — the visitor picks; phone numbers are normalised, e-mail addresses validated.
- **Consent, with your own policy** — the checkbox is required and links to the CMS page you select.
- **Abuse guards** — a session token, a honeypot field, and limits per browser and per address.
- **Submissions in the back office** — the hundred most recent, with date, channel, contact, page and address.
- **Optional notifications** — one e-mail per submission, or none if you prefer the list.

### What it stores, and what that means for GDPR

One row per submission: the channel, the contact the visitor typed, the consent flag, the page they were on, their IP address and the time. Nothing else, and nothing about customers who do not submit. The consent checkbox is required, so no row exists without it, and the text beside it links to your own policy.

The rows live in one table that the module creates on install and drops on uninstall — so uninstalling is also how you delete the data. If your retention policy is shorter than that, delete rows directly; there is no hidden copy anywhere else.

## Compatibility

| | |
|---|---|
| PrestaShop | 1.7.6 – 9.x |
| PHP | 7.4+ |
| Requirements | none — no external service, no captcha key |
| Multistore | Submissions record the shop they came from |
| Themes | Renders in `displayFooter`; the widget positions itself and needs no theme changes |

The module overrides no core class. It creates one table and two settings, and removes both when uninstalled.

## Installation

1. Upload and install the module from **Modules → Module Manager**.
2. Open its configuration and pick the CMS page holding your privacy policy.
3. Set the address that should be notified, or leave it empty to rely on the list.

The privacy policy page is guessed on install from your CMS pages, so the link usually works straight away — check that it points where you expect.

## Configuration options

| Setting | Description |
|---|---|
| **Privacy policy page** | The CMS page the consent text links to. Pick "none" to hide the link. |
| **Notify this address** | Where each submission is e-mailed. Empty means no e-mail; submissions still appear in the list. |

## Frequently asked questions

**Does it need a captcha key or an external service?**
No. The guards are a session token, a hidden honeypot field and rate limits counted in your own database.

**Can someone flood it?**
One submission per 20 seconds per browser and ten per hour per address. A caller who drops cookies still hits the address limit.

**What happens to submissions when I uninstall?**
They are deleted with the module's table. Export them first if you need them.

**Can I change where the button sits?**
It is positioned in CSS, in `views/css/widget.css` — override the `.m4p-contact-widget` rules from your theme.

---

**Keywords:** prestashop callback widget, prestashop request a call, prestashop contact button, prestashop lead capture, floating contact form prestashop

## License

MIT — see [LICENSE](LICENSE). Free to use commercially, fork and modify; keep the copyright notice.

## Contributing

Bug reports and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). For security
issues, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

---

Built by [Nice Code](https://nice-code.com/pl/oferta/moduly-prestashop) — we build and maintain PrestaShop stores.

© Nice Code sp. z o.o. (Modules4Presta) — released under the MIT license.
