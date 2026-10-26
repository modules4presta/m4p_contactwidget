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

/**
 * Hands out the form token and stores a "call me back" request from the widget; answers JSON.
 *
 * @property M4p_ContactWidget $module
 */
class M4p_ContactWidgetSubmitModuleFrontController extends ModuleFrontController
{
    /** One submission per session in this many seconds. */
    private const MIN_SECONDS_BETWEEN_SUBMITS = 20;

    /** How many submissions one address may send in an hour. */
    private const MAX_PER_IP_PER_HOUR = 10;

    /**
     * How many submissions one shop accepts in an hour, whatever address they claim to come from.
     * Behind a proxy the shop sees the address the request carries in X-Forwarded-For, so the
     * per-address count is backed by a per-shop one.
     */
    private const MAX_PER_SHOP_PER_HOUR = 100;

    public function __construct()
    {
        parent::__construct();
        // The base constructor sets $ajax from the request's `ajax` parameter.
        $this->ajax = true;
    }

    /**
     * Runs before any template rendering and exits, so this JSON endpoint never touches Smarty.
     */
    public function postProcess(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if (Tools::getValue('action') === 'token') {
            M4p_ContactWidget::issueNonce($this->context->cookie);
            $this->respond(true, '', ['token' => M4p_ContactWidget::formToken($this->context->cookie)]);
        }

        $expectedToken = M4p_ContactWidget::formToken($this->context->cookie);
        $token = (string) Tools::getValue('m4p_contact_token');
        if ($expectedToken === '' || $token === '' || !hash_equals($expectedToken, $token)) {
            $this->respond(false, $this->t('Your session has expired. Refresh the page and try again.'));
        }

        // Honeypot: a real visitor never fills this hidden field; bots get a success and nothing is saved.
        if ((string) Tools::getValue('m4p_hp') !== '') {
            $this->respond(true, $this->t('Thank you, we will contact you as soon as possible.'));
        }

        $lastSubmitAt = (int) $this->context->cookie->m4p_contact_last_submit;
        if ($lastSubmitAt > 0 && (time() - $lastSubmitAt) < self::MIN_SECONDS_BETWEEN_SUBMITS) {
            $this->respond(false, $this->t('Your request has already been sent. Please wait a moment before sending another one.'));
        }

        // The cookie guard above is per browser, so the stored rows are counted too: by address, and by
        // shop as the backstop that holds even when the address varies.
        if ($this->recentFromAddress() >= self::MAX_PER_IP_PER_HOUR
            || $this->recentForShop() >= self::MAX_PER_SHOP_PER_HOUR) {
            $this->respond(false, $this->t('Your request has already been sent. Please wait a moment before sending another one.'));
        }

        $channel = (string) Tools::getValue('channel');
        $rawValue = trim((string) Tools::getValue('contact_value'));

        if (!in_array($channel, ['phone', 'email'], true)) {
            $this->respond(false, $this->t('Invalid request.'));
        }
        if (!Tools::getValue('consent')) {
            $this->respond(false, $this->t('Tick the consent to the processing of your data.'));
        }

        if ($channel === 'email') {
            if (!Validate::isEmail($rawValue) || Tools::strlen($rawValue) > 191) {
                $this->respond(false, $this->t('Enter a valid e-mail address.'));
            }
            $value = $rawValue;
        } else {
            // Digits and one leading "+" only.
            $digits = preg_replace('/[^0-9+]/', '', $rawValue) ?? '';
            $value = preg_replace('/(?!^)\+/', '', $digits) ?? '';
            if (!preg_match('/^\+?[0-9]{7,15}$/', $value)) {
                $this->respond(false, $this->t('Enter a valid phone number.'));
            }
        }

        $pageUrl = $this->ownUrl((string) Tools::getValue('page_url'));
        // Written and compared with PHP's clock, like every other date PrestaShop stores, so the rate
        // windows do not depend on the time zone of the database session.
        $dateAdd = date('Y-m-d H:i:s');

        $inserted = Db::getInstance()->insert(M4p_ContactWidget::TABLE, [
            'channel' => pSQL($channel),
            'contact_value' => pSQL($value),
            'consent' => 1,
            'id_shop' => (int) $this->context->shop->id,
            'ip_address' => pSQL((string) Tools::getRemoteAddr()),
            'page_url' => pSQL($pageUrl),
            'date_add' => pSQL($dateAdd),
        ]);
        if (!$inserted) {
            $this->respond(false, $this->t('The request could not be saved. Please try again.'));
        }

        $this->notifyByEmail($channel, $value, $pageUrl, $dateAdd);
        $this->module->purgeOldRows();

        $this->context->cookie->m4p_contact_last_submit = time();
        $this->context->cookie->write();

        $this->respond(true, $this->t('Thank you, we will contact you as soon as possible.'));
    }

    /**
     * Best effort: the request is already saved, a failed e-mail must not lose it.
     */
    private function notifyByEmail(string $channel, string $value, string $pageUrl, string $dateAdd): void
    {
        $notifyEmail = (string) Configuration::get(M4p_ContactWidget::CFG_NOTIFY_EMAIL);
        if (!Validate::isEmail($notifyEmail)) {
            return;
        }

        // The shop owner reads this, so it goes out in the shop's language, not the visitor's.
        $idLang = $this->notifyLanguageId();
        $locale = Language::getLocaleById($idLang) ?: null;
        $translator = $this->module->getTranslator();

        try {
            Mail::send(
                $idLang,
                'contact_request',
                $translator->trans('New request from the contact widget', [], 'Modules.M4pcontactwidget.Admin', $locale),
                [
                    '{channel}' => $channel === 'phone'
                        ? $translator->trans('Phone', [], 'Modules.M4pcontactwidget.Admin', $locale)
                        : $translator->trans('E-mail', [], 'Modules.M4pcontactwidget.Admin', $locale),
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
                _PS_MODULE_DIR_ . $this->module->name . '/mails/',
                false,
                (int) $this->context->shop->id
            );
        } catch (Throwable $e) {
        }
    }

    /**
     * The shop's default language when the module carries a template for it, otherwise the first
     * installed language that has one.
     */
    private function notifyLanguageId(): int
    {
        $default = (int) Configuration::get('PS_LANG_DEFAULT');
        $candidates = [$default];
        foreach (Language::getLanguages(false) as $language) {
            $candidates[] = (int) $language['id_lang'];
        }

        foreach (array_unique($candidates) as $idLang) {
            $iso = Language::getIsoById($idLang);
            if ($iso && file_exists(_PS_MODULE_DIR_ . $this->module->name . '/mails/' . $iso . '/contact_request.html')) {
                return (int) $idLang;
            }
        }

        return $default;
    }

    private function recentFromAddress(): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . M4p_ContactWidget::TABLE . '`
             WHERE `ip_address` = \'' . pSQL((string) Tools::getRemoteAddr()) . '\'
               AND `date_add` > \'' . pSQL(self::oneHourAgo()) . '\''
        );
    }

    private function recentForShop(): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . M4p_ContactWidget::TABLE . '`
             WHERE `id_shop` = ' . (int) $this->context->shop->id . '
               AND `date_add` > \'' . pSQL(self::oneHourAgo()) . '\''
        );
    }

    private static function oneHourAgo(): string
    {
        return date('Y-m-d H:i:s', time() - 3600);
    }

    /**
     * Keeps only an address inside this shop: the value comes from the browser and is shown as a link
     * in the back-office list.
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

    private function t(string $text): string
    {
        return $this->module->getTranslator()->trans($text, [], 'Modules.M4pcontactwidget.Shop');
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return never
     */
    private function respond(bool $success, string $message, array $extra = []): void
    {
        echo json_encode(['success' => $success, 'message' => $message] + $extra);
        exit;
    }
}
