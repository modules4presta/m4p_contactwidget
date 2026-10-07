<?php

/**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

class M4p_ContactWidgetSubmitModuleFrontController extends ModuleFrontController
{
    /** One submission per session in this many seconds. */
    private const MIN_SECONDS_BETWEEN_SUBMITS = 20;

    /** How many submissions one address may send in an hour. */
    private const MAX_PER_IP_PER_HOUR = 10;

    public function __construct()
    {
        $this->ajax = true;

        parent::__construct();
    }

    /**
     * Runs before initContent()/template rendering (see Controller::run()),
     * so exiting here — like core's blockwishlist action controller does —
     * means we never touch Smarty at all for this pure-JSON endpoint.
     */
    public function postProcess(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Tools::isSubmit('m4p_contact_token') || !Tools::getValue('m4p_contact_token')) {
            $this->respond(false, $this->trans('Wrong request.', [], 'Modules.M4pcontactwidget.Shop'));
        }

        $expectedToken = Tools::getToken('m4p_contactwidget');
        $token = (string) Tools::getValue('m4p_contact_token');
        if (!hash_equals($expectedToken, $token)) {
            $this->respond(false, $this->trans('Your session expired. Refresh the page and try again.', [], 'Modules.M4pcontactwidget.Shop'));
        }

        // Honeypot: a real user never fills this hidden field.
        if ((string) Tools::getValue('m4p_hp') !== '') {
            $this->respond(true, $this->trans('Thank you, we will get back to you as soon as we can.', [], 'Modules.M4pcontactwidget.Shop'));
        }

        $lastSubmitAt = (int) $this->context->cookie->m4p_contact_last_submit;
        if ($lastSubmitAt > 0 && (time() - $lastSubmitAt) < self::MIN_SECONDS_BETWEEN_SUBMITS) {
            $this->respond(false, $this->trans('Your request has been sent. Please wait a moment before sending another.', [], 'Modules.M4pcontactwidget.Shop'));
        }

        // The cookie guard above is per browser and a caller who drops cookies
        // walks around it, so the address is counted against the stored rows too.
        if ($this->recentFromAddress() >= self::MAX_PER_IP_PER_HOUR) {
            $this->respond(false, $this->trans('Your request has been sent. Please wait a moment before sending another.', [], 'Modules.M4pcontactwidget.Shop'));
        }

        $channel = (string) Tools::getValue('channel');
        $rawValue = trim((string) Tools::getValue('contact_value'));
        $consent = (bool) Tools::getValue('consent');

        if (!in_array($channel, ['phone', 'email'], true)) {
            $this->respond(false, $this->trans('Wrong contact channel.', [], 'Modules.M4pcontactwidget.Shop'));
        }

        if (!$consent) {
            $this->respond(false, $this->trans('Consent to processing your data is required.', [], 'Modules.M4pcontactwidget.Shop'));
        }

        if ($channel === 'email') {
            if (!Validate::isEmail($rawValue) || Tools::strlen($rawValue) > 191) {
                $this->respond(false, $this->trans('Enter a valid e-mail address.', [], 'Modules.M4pcontactwidget.Shop'));
            }
            $value = $rawValue;
        } else {
            // Keep digits and a single leading "+" only.
            $digits = preg_replace('/[^0-9+]/', '', $rawValue) ?? '';
            $normalized = preg_replace('/(?!^)\+/', '', $digits) ?? '';
            if ($normalized === '' || !preg_match('/^\+?[0-9]{7,15}$/', $normalized)) {
                $this->respond(false, $this->trans('Enter a valid phone number.', [], 'Modules.M4pcontactwidget.Shop'));
            }
            $value = $normalized;
        }

        $pageUrl = $this->ownUrl((string) Tools::getValue('page_url'));
        $dateAdd = date('Y-m-d H:i:s');

        // Db::insert() binds/escapes every value (pSQL under the hood) — no
        // string concatenation anywhere, so this is not SQL-injectable.
        $inserted = Db::getInstance()->insert(M4p_ContactWidget::TABLE, [
            'channel' => pSQL($channel),
            'contact_value' => pSQL($value),
            'consent' => 1,
            'id_shop' => (int) $this->context->shop->id,
            'ip_address' => pSQL((string) Tools::getRemoteAddr()),
            'page_url' => pSQL($pageUrl),
            'date_add' => $dateAdd,
        ]);

        if (!$inserted) {
            $this->respond(false, $this->trans('The request could not be saved. Please try again.', [], 'Modules.M4pcontactwidget.Shop'));
        }

        $this->notifyByEmail($channel, $value, $pageUrl, $dateAdd);

        $this->context->cookie->m4p_contact_last_submit = time();
        $this->context->cookie->write();

        $this->respond(true, $this->trans('Thank you, we will get back to you as soon as we can.', [], 'Modules.M4pcontactwidget.Shop'));
    }

    /**
     * Best-effort: a failed notification e-mail must never lose the lead,
     * which is already safely in the DB by the time this runs.
     */
    private function notifyByEmail(string $channel, string $value, string $pageUrl, string $dateAdd): void
    {
        $notifyEmail = (string) Configuration::get(M4p_ContactWidget::CFG_NOTIFY_EMAIL);
        if ($notifyEmail === '' || !Validate::isEmail($notifyEmail)) {
            return;
        }

        try {
            Mail::send(
                (int) $this->context->language->id,
                'contact_request',
                $this->trans('New callback request', [], 'Modules.M4pcontactwidget.Shop'),
                [
                    '{channel}' => $channel === 'phone' ? $this->trans('Phone', [], 'Modules.M4pcontactwidget.Shop') : $this->trans('E-mail', [], 'Modules.M4pcontactwidget.Shop'),
                    '{contact_value}' => $value,
                    '{page_url}' => $pageUrl,
                    '{date_add}' => $dateAdd,
                    '{ip_address}' => (string) Tools::getRemoteAddr(),
                ],
                $notifyEmail,
                null,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . 'm4p_contactwidget/mails/'
            );
        } catch (\Throwable $e) {
            // Swallow: notification is a nice-to-have, the lead is already saved.
        }
    }

    private function recentFromAddress(): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . M4p_ContactWidget::TABLE . '`
             WHERE `ip_address` = "' . pSQL((string) Tools::getRemoteAddr()) . '"
               AND `date_add` > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
    }

    /**
     * Keeps only an address inside this shop.
     *
     * The value is submitted by the browser and ends up as a link on the module's
     * configuration page, where an http prefix is what separates a page address
     * from something the admin's browser would execute on click.
     */
    private function ownUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $base = $this->context->shop->getBaseURL(true);

        if (strpos($url, $base) !== 0 || !Validate::isUrl($url)) {
            return '';
        }

        return Tools::substr($url, 0, 512);
    }

    /**
     * @return never
     */
    private function respond(bool $success, string $message): void
    {
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
}
