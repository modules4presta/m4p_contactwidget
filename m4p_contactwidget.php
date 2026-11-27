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

require_once __DIR__ . '/classes/M4pContactSchedule.php';

/**
 * Floating contact widget in the corner of the shop: a labelled button with the desk's open/closed status,
 * a panel with phone, e-mail and opening hours from the back office, plus a "we will call you back" form
 * (phone or e-mail with consent) stored in its own table.
 */
class M4p_ContactWidget extends Module
{
    public const TABLE = 'm4p_contact_request';
    public const ADMIN_CONTROLLER = 'AdminM4pContactRequests';
    public const CFG_PRIVACY_CMS_ID = 'M4P_CONTACTWIDGET_PRIVACY_CMS_ID';
    public const CFG_NOTIFY_EMAIL = 'M4P_CONTACTWIDGET_NOTIFY_EMAIL';
    public const CFG_RETENTION_DAYS = 'M4P_CONTACTWIDGET_RETENTION_DAYS';
    public const CFG_LAST_PURGE = 'M4P_CONTACTWIDGET_LAST_PURGE';
    public const CFG_PHONE = 'M4P_CONTACTWIDGET_PHONE';
    public const CFG_EMAIL = 'M4P_CONTACTWIDGET_EMAIL';
    public const CFG_MODULE_CSS = 'M4P_CONTACTWIDGET_MODULE_CSS';
    /** Per-language texts. */
    public const CFG_TITLE = 'M4P_CONTACTWIDGET_TITLE';
    public const CFG_INTRO = 'M4P_CONTACTWIDGET_INTRO';
    public const CFG_HOURS = 'M4P_CONTACTWIDGET_HOURS';
    public const CFG_BUBBLE = 'M4P_CONTACTWIDGET_BUBBLE';
    public const CFG_LABEL = 'M4P_CONTACTWIDGET_LABEL';
    public const CFG_CALLBACK_OPEN = 'M4P_CONTACTWIDGET_CALLBACK_OPEN';
    public const CFG_CALLBACK_CLOSED = 'M4P_CONTACTWIDGET_CALLBACK_CLOSED';
    /** Opening hours: JSON of ISO weekday => [from, to], and dated exceptions, one per line. */
    public const CFG_SCHEDULE = 'M4P_CONTACTWIDGET_SCHEDULE';
    /** The sales department in m4p_core; when filled in it replaces the widget's own phone and weekly hours. */
    public const CORE_PHONE = 'M4P_CORE_SALES_PHONE';
    public const CORE_SCHEDULE = 'M4P_CORE_SALES_HOURS';
    public const CFG_EXCEPTIONS = 'M4P_CONTACTWIDGET_EXCEPTIONS';
    /** Extra distance from the bottom edge on phones, in px. */
    public const CFG_OFFSET_MOBILE = 'M4P_CONTACTWIDGET_OFFSET_MOBILE';
    /** CSS selectors of page elements the button steps aside for while it would cover them. */
    public const CFG_AVOID = 'M4P_CONTACTWIDGET_AVOID';
    /** Channel switches: the phone and e-mail rows, and the two options of the call-back form. */
    public const CFG_SHOW_PHONE = 'M4P_CONTACTWIDGET_SHOW_PHONE';
    /** Next to the button label: open now / opens at, with the status dot. */
    public const CFG_FAB_STATUS = 'M4P_CONTACTWIDGET_FAB_STATUS';
    public const CFG_SHOW_EMAIL = 'M4P_CONTACTWIDGET_SHOW_EMAIL';
    public const CFG_FORM_PHONE = 'M4P_CONTACTWIDGET_FORM_PHONE';
    public const CFG_FORM_EMAIL = 'M4P_CONTACTWIDGET_FORM_EMAIL';
    /** Comma-separated keys of PAGE_GROUPS the widget is hidden on. */
    public const CFG_HIDDEN_PAGES = 'M4P_CONTACTWIDGET_HIDDEN_PAGES';
    /** Per-language form texts and the privacy link address used instead of the CMS page. */
    public const CFG_FORM_TITLE_PHONE = 'M4P_CONTACTWIDGET_FORM_TITLE_PHONE';
    public const CFG_FORM_TITLE_EMAIL = 'M4P_CONTACTWIDGET_FORM_TITLE_EMAIL';
    public const CFG_SUBMIT_PHONE = 'M4P_CONTACTWIDGET_SUBMIT_PHONE';
    public const CFG_SUBMIT_EMAIL = 'M4P_CONTACTWIDGET_SUBMIT_EMAIL';
    public const CFG_CONSENT = 'M4P_CONTACTWIDGET_CONSENT';
    public const CFG_PRIVACY_LABEL = 'M4P_CONTACTWIDGET_PRIVACY_LABEL';
    public const CFG_PRIVACY_URL = 'M4P_CONTACTWIDGET_PRIVACY_URL';
    public const CFG_SUCCESS = 'M4P_CONTACTWIDGET_SUCCESS';

    /** Name of the cookie entry holding the per-visitor form secret. */
    public const COOKIE_NONCE = 'm4p_contact_nonce';

    private const CFG_KEYS = [
        self::CFG_PRIVACY_CMS_ID,
        self::CFG_NOTIFY_EMAIL,
        self::CFG_RETENTION_DAYS,
        self::CFG_LAST_PURGE,
        self::CFG_PHONE,
        self::CFG_EMAIL,
        self::CFG_MODULE_CSS,
        self::CFG_TITLE,
        self::CFG_INTRO,
        self::CFG_HOURS,
        self::CFG_BUBBLE,
        self::CFG_LABEL,
        self::CFG_CALLBACK_OPEN,
        self::CFG_CALLBACK_CLOSED,
        self::CFG_SCHEDULE,
        self::CFG_EXCEPTIONS,
        self::CFG_OFFSET_MOBILE,
        self::CFG_AVOID,
        self::CFG_SHOW_PHONE,
        self::CFG_FAB_STATUS,
        self::CFG_SHOW_EMAIL,
        self::CFG_FORM_PHONE,
        self::CFG_FORM_EMAIL,
        self::CFG_HIDDEN_PAGES,
        self::CFG_FORM_TITLE_PHONE,
        self::CFG_FORM_TITLE_EMAIL,
        self::CFG_SUBMIT_PHONE,
        self::CFG_SUBMIT_EMAIL,
        self::CFG_CONSENT,
        self::CFG_PRIVACY_LABEL,
        self::CFG_PRIVACY_URL,
        self::CFG_SUCCESS,
    ];

    private const LANG_KEYS = [
        self::CFG_TITLE,
        self::CFG_INTRO,
        self::CFG_HOURS,
        self::CFG_BUBBLE,
        self::CFG_LABEL,
        self::CFG_CALLBACK_OPEN,
        self::CFG_CALLBACK_CLOSED,
        self::CFG_FORM_TITLE_PHONE,
        self::CFG_FORM_TITLE_EMAIL,
        self::CFG_SUBMIT_PHONE,
        self::CFG_SUBMIT_EMAIL,
        self::CFG_CONSENT,
        self::CFG_PRIVACY_LABEL,
        self::CFG_PRIVACY_URL,
        self::CFG_SUCCESS,
    ];

    private const SWITCH_KEYS = [
        self::CFG_SHOW_PHONE,
        self::CFG_FAB_STATUS,
        self::CFG_SHOW_EMAIL,
        self::CFG_FORM_PHONE,
        self::CFG_FORM_EMAIL,
    ];

    /** Largest bottom offset on phones, in px. */
    private const OFFSET_MAX = 200;

    /** Rows kept per shop unless the setting says otherwise. */
    private const DEFAULT_RETENTION_DAYS = 365;

    /** At most one purge per shop in this many seconds. */
    private const PURGE_INTERVAL = 86400;

    /** Page types offered in the settings, by the php_self of their controllers; 'other' takes the rest. */
    private const PAGE_GROUPS = [
        'index' => ['index'],
        'category' => ['category'],
        'product' => ['product'],
        'cms' => ['cms'],
        'search' => ['search'],
        'brands' => ['manufacturer', 'supplier'],
        'listings' => ['best-sales', 'new-products', 'prices-drop'],
        'contact' => ['contact', 'stores'],
        'cart' => ['cart'],
        'order' => ['order'],
        'order_confirmation' => ['order-confirmation'],
        'login' => ['authentication', 'registration', 'password'],
        'account' => ['my-account', 'identity', 'address', 'addresses', 'history', 'order-detail', 'order-follow', 'order-slip', 'discount', 'guest-tracking'],
        'other' => [],
    ];

    /** The cart and the checkout have their own help line. */
    private const DEFAULT_HIDDEN_PAGES = 'cart,order,order_confirmation';

    private const ICONS = ['phone', 'envelope', 'clock', 'xmark', 'paper-plane', 'circle-check', 'circle-alert', 'chevron-right'];

    public function __construct()
    {
        $this->name = 'm4p_contactwidget';
        $this->tab = 'front_office_features';
        $this->version = '3.5.0';
        $this->author = 'Modules4Presta';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Contact widget', [], 'Modules.M4pcontactwidget.Admin');
        $this->description = $this->trans('A floating button in the corner of the shop with the phone, e-mail, opening hours and a "we will call you back" form.', [], 'Modules.M4pcontactwidget.Admin');
        $this->confirmUninstall = $this->trans('Remove the module? The submissions collected so far are deleted with it.', [], 'Modules.M4pcontactwidget.Admin');
    }

    public function install(): bool
    {
        // Best guess for the privacy policy page, so the consent link works out of the box.
        $guessedCmsId = (int) Db::getInstance()->getValue(
            'SELECT c.id_cms FROM `' . _DB_PREFIX_ . 'cms` c
             JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON cl.id_cms = c.id_cms
             WHERE c.active = 1 AND (cl.link_rewrite LIKE \'%polityka-prywatnosci%\' OR cl.link_rewrite LIKE \'%privacy%\')'
        );

        Configuration::updateValue(self::CFG_PRIVACY_CMS_ID, $guessedCmsId);
        Configuration::updateValue(self::CFG_NOTIFY_EMAIL, Configuration::get('PS_SHOP_EMAIL'));
        $this->applyDefaults();

        return parent::install()
            && $this->installDb()
            && $this->installTab()
            && $this->registerHooks();
    }

    public function uninstall(): bool
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE . '`');

        foreach (self::CFG_KEYS as $key) {
            Configuration::deleteByName($key);
        }

        return $this->uninstallTab() && parent::uninstall();
    }

    public function registerHooks(): bool
    {
        return $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionExportGDPRData')
            && $this->registerHook('actionDeleteGDPRCustomer')
            && $this->registerHook('actionObjectShopDeleteAfter');
    }

    /**
     * Customers > Contact widget requests.
     */
    public function installTab(): bool
    {
        if (Tab::getIdFromClassName(self::ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = self::ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentCustomer');
        $tab->active = true;
        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = $this->trans('Contact widget requests', [], 'Modules.M4pcontactwidget.Admin', $language['locale']);
        }

        return (bool) $tab->add();
    }

    private function uninstallTab(): bool
    {
        $idTab = (int) Tab::getIdFromClassName(self::ADMIN_CONTROLLER);

        return !$idTab || (new Tab($idTab))->delete();
    }

    /**
     * Writes the settings that are not stored yet: contact data from the shop settings, the retention
     * period and default texts in every language. Used on install and upgrade; saved values stay.
     */
    public function applyDefaults(): void
    {
        $plain = [
            self::CFG_PHONE => (string) Configuration::get('PS_SHOP_PHONE'),
            self::CFG_EMAIL => (string) Configuration::get('PS_SHOP_EMAIL'),
            self::CFG_MODULE_CSS => 1,
            self::CFG_RETENTION_DAYS => self::DEFAULT_RETENTION_DAYS,
            self::CFG_SCHEDULE => '',
            self::CFG_EXCEPTIONS => '',
            self::CFG_OFFSET_MOBILE => 0,
            self::CFG_AVOID => '',
            self::CFG_SHOW_PHONE => 1,
            self::CFG_FAB_STATUS => 1,
            self::CFG_SHOW_EMAIL => 1,
            self::CFG_FORM_PHONE => 1,
            self::CFG_FORM_EMAIL => 1,
            self::CFG_HIDDEN_PAGES => self::DEFAULT_HIDDEN_PAGES,
        ];
        foreach ($plain as $key => $value) {
            if (!Configuration::hasKey($key)) {
                Configuration::updateValue($key, $value);
            }
        }

        $texts = array_fill_keys(self::LANG_KEYS, []);
        foreach (Language::getLanguages(false) as $language) {
            $id = (int) $language['id_lang'];
            $locale = (string) $language['locale'];
            $texts[self::CFG_TITLE][$id] = $this->trans('Need help?', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_INTRO][$id] = $this->trans('Call or write to us, or leave your number and we will call you back.', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_HOURS][$id] = '';
            $texts[self::CFG_BUBBLE][$id] = $this->trans('Have a question? We are happy to help.', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_LABEL][$id] = $this->trans('Contact', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_CALLBACK_OPEN][$id] = $this->trans('We call back during the opening hours of the desk.', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_CALLBACK_CLOSED][$id] = $this->trans('We will call you back as soon as we open.', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_FORM_TITLE_PHONE][$id] = $this->trans('We will call you back', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_FORM_TITLE_EMAIL][$id] = $this->trans('We will write back to you', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_SUBMIT_PHONE][$id] = $this->trans('Call me back', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_SUBMIT_EMAIL][$id] = $this->trans('Send', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_CONSENT][$id] = $this->trans('I agree to the processing of my personal data in order to be contacted.', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_PRIVACY_LABEL][$id] = $this->trans('Privacy policy', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_PRIVACY_URL][$id] = '';
            $texts[self::CFG_SUCCESS][$id] = $this->trans('Thank you, we will contact you as soon as possible.', [], 'Modules.M4pcontactwidget.Shop', $locale);
        }
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        foreach ($texts as $key => $values) {
            if (!Configuration::hasKey($key, $idLangDefault)) {
                Configuration::updateValue($key, $values);
            }
        }
    }

    public function installDb(): bool
    {
        // ip_date carries the per-address rate check and shop_date both the back-office list and the
        // retention purge, which run on every submission and would otherwise scan the whole table.
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
            `id_m4p_contact_request` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `channel` ENUM(\'phone\',\'email\') NOT NULL,
            `contact_value` VARCHAR(191) NOT NULL,
            `consent` TINYINT(1) NOT NULL DEFAULT 0,
            `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
            `ip_address` VARCHAR(45) NOT NULL DEFAULT \'\',
            `page_url` VARCHAR(512) NOT NULL DEFAULT \'\',
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_m4p_contact_request`),
            KEY `ip_date` (`ip_address`, `date_add`),
            KEY `shop_date` (`id_shop`, `date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        return Db::getInstance()->execute($sql);
    }

    /**
     * The secret behind the form token, kept in the visitor's cookie. Issued by the submit controller
     * when the panel asks for a token, so the page markup stays the same for every visitor.
     */
    public static function issueNonce(Cookie $cookie): void
    {
        if ((string) $cookie->{self::COOKIE_NONCE} !== '') {
            return;
        }

        $cookie->{self::COOKIE_NONCE} = Tools::passwdGen(32, 'ALPHANUMERIC');
        $cookie->write();
    }

    /**
     * Token the form carries: tied to the visitor's own cookie, so it is neither guessable nor reusable
     * from another browser.
     */
    public static function formToken(Cookie $cookie): string
    {
        $nonce = (string) $cookie->{self::COOKIE_NONCE};

        return $nonce === '' ? '' : Tools::hash($nonce . 'm4p_contactwidget');
    }

    private function isShownHere(): bool
    {
        $page = (string) ($this->context->controller->php_self ?? '');
        $group = 'other';
        foreach (self::PAGE_GROUPS as $key => $pages) {
            if (in_array($page, $pages, true)) {
                $group = $key;
                break;
            }
        }

        return !in_array($group, $this->hiddenPages(), true);
    }

    /**
     * @return string[]
     */
    private function hiddenPages(): array
    {
        $value = Configuration::get(self::CFG_HIDDEN_PAGES);
        if ($value === false) {
            $value = self::DEFAULT_HIDDEN_PAGES;
        }

        return array_values(array_intersect(explode(',', (string) $value), array_keys(self::PAGE_GROUPS)));
    }

    /**
     * Form options switched on, in display order.
     *
     * @return string[]
     */
    public static function formChannels(): array
    {
        $channels = [];
        if ((int) Configuration::get(self::CFG_FORM_PHONE)) {
            $channels[] = 'phone';
        }
        if ((int) Configuration::get(self::CFG_FORM_EMAIL)) {
            $channels[] = 'email';
        }

        return $channels;
    }

    private function privacyUrl(int $idLang): string
    {
        $url = trim((string) Configuration::get(self::CFG_PRIVACY_URL, $idLang));
        if ($url !== '') {
            return $url;
        }
        $idCms = (int) Configuration::get(self::CFG_PRIVACY_CMS_ID);

        return $idCms > 0 ? $this->context->link->getCMSLink($idCms) : '';
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        if (!$this->isShownHere()) {
            return;
        }

        if (Configuration::get(self::CFG_MODULE_CSS)) {
            $this->context->controller->registerStylesheet(
                'm4p-contactwidget',
                'modules/' . $this->name . '/views/css/widget.css',
                ['media' => 'all', 'priority' => 1000, 'version' => (string) filemtime(__DIR__ . '/views/css/widget.css')]
            );
        }
        $this->context->controller->registerJavascript(
            'm4p-contactwidget',
            'modules/' . $this->name . '/views/js/widget.js',
            ['position' => 'bottom', 'priority' => 200, 'version' => (string) filemtime(__DIR__ . '/views/js/widget.js')]
        );
    }

    /**
     * The markup is the same for every visitor (the form token is fetched when the panel opens), so
     * full-page caches can keep it.
     */
    public static function phone(): string
    {
        $core = Module::isEnabled('m4p_core') ? trim((string) Configuration::get(self::CORE_PHONE)) : '';

        return $core !== '' ? $core : trim((string) Configuration::get(self::CFG_PHONE));
    }

    public static function scheduleJson(): string
    {
        $core = Module::isEnabled('m4p_core') ? trim((string) Configuration::get(self::CORE_SCHEDULE)) : '';

        return $core !== '' ? $core : (string) Configuration::get(self::CFG_SCHEDULE);
    }

    public function hookDisplayBeforeBodyClosingTag(): string
    {
        if (!$this->isShownHere()) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $phone = (int) Configuration::get(self::CFG_SHOW_PHONE) ? self::phone() : '';
        $email = (int) Configuration::get(self::CFG_SHOW_EMAIL) ? trim((string) Configuration::get(self::CFG_EMAIL)) : '';
        $hours = (string) Configuration::get(self::CFG_HOURS, $idLang);
        $formChannels = self::formChannels();

        $schedule = M4pContactSchedule::fromConfiguration();
        if ($phone === '' && !Validate::isEmail($email) && !$formChannels && !$schedule->isConfigured() && $hours === '') {
            return '';
        }
        $now = new DateTimeImmutable('now', $schedule->timezone());

        $this->context->smarty->assign('m4p_contact', [
            'label' => (string) Configuration::get(self::CFG_LABEL, $idLang) ?: $this->trans('Contact', [], 'Modules.M4pcontactwidget.Shop'),
            'callback_open' => (string) Configuration::get(self::CFG_CALLBACK_OPEN, $idLang),
            'callback_closed' => (string) Configuration::get(self::CFG_CALLBACK_CLOSED, $idLang),
            'offset_mobile' => max(0, min(self::OFFSET_MAX, (int) Configuration::get(self::CFG_OFFSET_MOBILE))),
            'avoid' => (string) Configuration::get(self::CFG_AVOID),
            'schedule' => $schedule->isConfigured() ? $this->scheduleView($schedule, $now) : null,
            'fab_status' => (bool) Configuration::get(self::CFG_FAB_STATUS),
            'title' => (string) Configuration::get(self::CFG_TITLE, $idLang),
            'intro' => (string) Configuration::get(self::CFG_INTRO, $idLang),
            'hours' => $hours,
            'bubble' => (string) Configuration::get(self::CFG_BUBBLE, $idLang),
            'phone' => $phone,
            'phone_href' => preg_replace('/[^0-9+]/', '', $phone),
            'email' => Validate::isEmail($email) ? $email : '',
            'form_channels' => $formChannels,
            'form_title_phone' => (string) Configuration::get(self::CFG_FORM_TITLE_PHONE, $idLang),
            'form_title_email' => (string) Configuration::get(self::CFG_FORM_TITLE_EMAIL, $idLang),
            'submit_phone' => (string) Configuration::get(self::CFG_SUBMIT_PHONE, $idLang),
            'submit_email' => (string) Configuration::get(self::CFG_SUBMIT_EMAIL, $idLang),
            'consent' => (string) Configuration::get(self::CFG_CONSENT, $idLang),
            'privacy_label' => (string) Configuration::get(self::CFG_PRIVACY_LABEL, $idLang),
            'privacy_url' => $this->privacyUrl($idLang),
            'submit_url' => $this->context->link->getModuleLink($this->name, 'submit', [], true),
            'icons' => $this->getIcons(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/widget.tpl');
    }

    /**
     * Lucide icons (ISC licence) shipped in views/img, inlined as decorative SVG.
     *
     * @return array<string, string>
     */
    private function getIcons(): array
    {
        $icons = [];
        foreach (self::ICONS as $name) {
            $icons[$name] = str_replace(
                '<svg ',
                '<svg class="m4p-contact-widget__svg" aria-hidden="true" focusable="false" ',
                (string) Tools::file_get_contents(__DIR__ . '/views/img/' . $name . '.svg')
            );
        }

        return $icons;
    }

    /**
     * Status for the first render, the opening periods the script keeps the status current with, the weekly
     * hours grouped into rows and the upcoming exceptions.
     *
     * @return array<string, mixed>
     */
    private function scheduleView(M4pContactSchedule $schedule, DateTimeImmutable $now): array
    {
        $timezone = $schedule->timezone();
        $periods = [];
        foreach ($schedule->periods($now) as $period) {
            $day = new DateTimeImmutable($period['date'], $timezone);
            $periods[] = $period + [
                'day' => $this->shortDay((int) $day->format('N')) . ' ' . $day->format('j.m'),
                'from' => (new DateTimeImmutable('@' . $period['start']))->setTimezone($timezone)->format('G:i'),
                'to' => (new DateTimeImmutable('@' . $period['end']))->setTimezone($timezone)->format('G:i'),
            ];
        }

        $labels = $this->statusLabels();
        $today = $now->format('Y-m-d');
        $tomorrow = $now->modify('+1 day')->format('Y-m-d');
        $status = ['open' => false, 'button' => '', 'badge' => $labels['closed']];
        foreach ($periods as $period) {
            if ($now->getTimestamp() >= $period['end']) {
                continue;
            }
            if ($now->getTimestamp() >= $period['start']) {
                $status = ['open' => true, 'button' => str_replace('%time%', $period['to'], $labels['button_open']), 'badge' => str_replace('%time%', $period['to'], $labels['badge_open'])];
                break;
            }
            $when = $period['date'] === $today ? 'today' : ($period['date'] === $tomorrow ? 'tomorrow' : 'later');
            $status = [
                'open' => false,
                'button' => str_replace(['%time%', '%day%'], [$period['from'], $period['day']], $labels['button_' . $when]),
                'badge' => str_replace(['%time%', '%day%'], [$period['from'], $period['day']], $labels['badge_' . $when]),
            ];
            break;
        }

        $rows = [];
        $todayNumber = (int) $now->format('N');
        $todayRange = $schedule->rangeOn($now);
        foreach ($schedule->week() as $day => $range) {
            $last = end($rows);
            if ($last && $last['range'] === $range && $last['to'] === $day - 1) {
                $rows[key($rows)]['to'] = $day;
                continue;
            }
            $rows[] = ['from' => $day, 'to' => $day, 'range' => $range];
        }
        $hours = [];
        foreach ($rows as $row) {
            $hours[] = [
                'days' => $row['from'] === $row['to'] ? $this->longDay($row['from']) : $this->capitalize($this->shortDay($row['from'])) . '–' . $this->shortDay($row['to']),
                'hours' => $row['range'] ? $this->displayTime($row['range'][0]) . '–' . $this->displayTime($row['range'][1]) : '',
                'today' => $todayNumber >= $row['from'] && $todayNumber <= $row['to'] && $todayRange === $row['range'],
            ];
        }
        $exceptions = [];
        foreach ($schedule->upcomingExceptions($now) as $date => $range) {
            $exceptions[] = [
                'days' => (new DateTimeImmutable($date, $timezone))->format('j.m'),
                'hours' => $range ? $this->displayTime($range[0]) . '–' . $this->displayTime($range[1]) : '',
                'today' => $date === $today,
            ];
        }

        return [
            'status' => $status,
            'hours' => $hours,
            'exceptions' => $exceptions,
            'script' => [
                'timezone' => $timezone->getName(),
                'periods' => array_map(static function (array $period): array {
                    return ['date' => $period['date'], 'start' => $period['start'], 'end' => $period['end'], 'day' => $period['day'], 'from' => $period['from'], 'to' => $period['to']];
                }, $periods),
                'labels' => $labels,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'closed' => $this->trans('Closed', [], 'Modules.M4pcontactwidget.Shop'),
            'button_open' => $this->trans('open until %time%', [], 'Modules.M4pcontactwidget.Shop'),
            'badge_open' => $this->trans('Open until %time%', [], 'Modules.M4pcontactwidget.Shop'),
            'button_today' => $this->trans('from %time%', [], 'Modules.M4pcontactwidget.Shop'),
            'badge_today' => $this->trans('Closed, we open today at %time%', [], 'Modules.M4pcontactwidget.Shop'),
            'button_tomorrow' => $this->trans('tomorrow from %time%', [], 'Modules.M4pcontactwidget.Shop'),
            'badge_tomorrow' => $this->trans('Closed, we open tomorrow at %time%', [], 'Modules.M4pcontactwidget.Shop'),
            'button_later' => $this->trans('%day% from %time%', [], 'Modules.M4pcontactwidget.Shop'),
            'badge_later' => $this->trans('Closed, we open %day% at %time%', [], 'Modules.M4pcontactwidget.Shop'),
        ];
    }

    private function capitalize(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    /** 07:30 as 7:30. */
    private function displayTime(string $time): string
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours . ':' . $minutes;
    }

    private function shortDay(int $day): string
    {
        $names = [
            1 => $this->trans('Mon', [], 'Modules.M4pcontactwidget.Shop'),
            2 => $this->trans('Tue', [], 'Modules.M4pcontactwidget.Shop'),
            3 => $this->trans('Wed', [], 'Modules.M4pcontactwidget.Shop'),
            4 => $this->trans('Thu', [], 'Modules.M4pcontactwidget.Shop'),
            5 => $this->trans('Fri', [], 'Modules.M4pcontactwidget.Shop'),
            6 => $this->trans('Sat', [], 'Modules.M4pcontactwidget.Shop'),
            7 => $this->trans('Sun', [], 'Modules.M4pcontactwidget.Shop'),
        ];

        return $names[$day];
    }

    private function longDay(int $day): string
    {
        $names = [
            1 => $this->trans('Monday', [], 'Modules.M4pcontactwidget.Shop'),
            2 => $this->trans('Tuesday', [], 'Modules.M4pcontactwidget.Shop'),
            3 => $this->trans('Wednesday', [], 'Modules.M4pcontactwidget.Shop'),
            4 => $this->trans('Thursday', [], 'Modules.M4pcontactwidget.Shop'),
            5 => $this->trans('Friday', [], 'Modules.M4pcontactwidget.Shop'),
            6 => $this->trans('Saturday', [], 'Modules.M4pcontactwidget.Shop'),
            7 => $this->trans('Sunday', [], 'Modules.M4pcontactwidget.Shop'),
        ];

        return $names[$day];
    }

    /**
     * Deletes rows older than the retention setting of the current shop, at most once a day per shop.
     * Called on every submission and when the back-office list opens.
     */
    public function purgeOldRows(): void
    {
        $days = (int) Configuration::get(self::CFG_RETENTION_DAYS);
        if ($days <= 0) {
            return;
        }

        $last = (int) Configuration::get(self::CFG_LAST_PURGE);
        if ($last > 0 && (time() - $last) < self::PURGE_INTERVAL) {
            return;
        }

        // The cut-off is computed in PHP, the clock the rows were written with, so the retention period
        // does not shift with the time zone the database session runs in.
        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE `id_shop` = ' . (int) $this->context->shop->id . '
               AND `date_add` < \'' . pSQL(date('Y-m-d H:i:s', time() - ($days * 86400))) . '\''
        );

        Configuration::updateValue(self::CFG_LAST_PURGE, time());
    }

    /**
     * Submissions the psgdpr module exports for a customer, matched on the e-mail address they left
     * in the widget.
     *
     * @param array<string, mixed> $params
     *
     * @return string|bool
     */
    public function hookActionExportGDPRData(array $params)
    {
        $email = isset($params['email']) ? (string) $params['email'] : '';
        if ($email === '' || !Validate::isEmail($email)) {
            return false;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `date_add`, `channel`, `contact_value`, `page_url`, `ip_address`
             FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE `contact_value` = \'' . pSQL($email) . '\'
             ORDER BY `date_add` DESC'
        );

        return json_encode($rows ?: []);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return string|bool
     */
    public function hookActionDeleteGDPRCustomer(array $params)
    {
        $email = isset($params['email']) ? (string) $params['email'] : '';
        if ($email === '' || !Validate::isEmail($email)) {
            return false;
        }

        $deleted = Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE `contact_value` = \'' . pSQL($email) . '\''
        );

        return $deleted ? '1' : false;
    }

    /**
     * Submissions belong to the shop they were left in, so a shop that is removed takes them with it.
     *
     * @param array<string, mixed> $params
     */
    public function hookActionObjectShopDeleteAfter(array $params): void
    {
        if (!isset($params['object']) || !$params['object'] instanceof Shop) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE `id_shop` = ' . (int) $params['object']->id
        );
    }

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submitM4pContactWidget')) {
            $output .= $this->saveSettings();
        }

        $requestsUrl = $this->context->link->getAdminLink(self::ADMIN_CONTROLLER);
        // trans() escapes its parameters, so the link goes in after translating.
        $output .= '<div class="alert alert-info">' . str_replace(
            '%link%',
            '<a href="' . htmlspecialchars($requestsUrl, ENT_QUOTES) . '">' . $this->trans('Contact widget requests', [], 'Modules.M4pcontactwidget.Admin') . '</a>',
            $this->trans('Requests sent from the widget are listed in Customers > %link%.', [], 'Modules.M4pcontactwidget.Admin')
        ) . '</div>';

        return $output . $this->renderConfigForm();
    }

    private function saveSettings(): string
    {
        $notifyEmail = trim((string) Tools::getValue(self::CFG_NOTIFY_EMAIL));
        $email = trim((string) Tools::getValue(self::CFG_EMAIL));
        $retention = (int) Tools::getValue(self::CFG_RETENTION_DAYS);

        if (($notifyEmail !== '' && !Validate::isEmail($notifyEmail)) || ($email !== '' && !Validate::isEmail($email))) {
            return $this->displayError($this->trans('That e-mail address is not valid, so nothing was saved.', [], 'Modules.M4pcontactwidget.Admin'));
        }

        if ($retention < 0) {
            return $this->displayError($this->trans('Enter the number of days as 0 or more.', [], 'Modules.M4pcontactwidget.Admin'));
        }

        $week = [];
        foreach (range(1, 7) as $day) {
            $value = trim((string) Tools::getValue('m4p_hours_' . $day));
            if ($value === '') {
                continue;
            }
            $parts = preg_split('/\s*[-–]\s*/u', $value) ?: [];
            if (count($parts) !== 2 || !M4pContactSchedule::isRange($parts[0], $parts[1])) {
                return $this->displayError($this->trans('Enter the hours as from–to, e.g. 7:30-16:00, or leave the day empty when the desk is closed. Nothing was saved.', [], 'Modules.M4pcontactwidget.Admin'));
            }
            $week[$day] = [M4pContactSchedule::normalizeTime($parts[0]), M4pContactSchedule::normalizeTime($parts[1])];
        }
        $exceptions = trim((string) Tools::getValue(self::CFG_EXCEPTIONS));
        $validLines = count(M4pContactSchedule::parseExceptions($exceptions));
        $lines = count(array_filter(array_map('trim', preg_split('/\R/', $exceptions) ?: [])));
        if ($validLines !== $lines) {
            return $this->displayError($this->trans('Write each exception on its own line as a date and hours, e.g. 2026-12-24 8:00-12:00, or a date and "closed". Nothing was saved.', [], 'Modules.M4pcontactwidget.Admin'));
        }
        $offset = (int) Tools::getValue(self::CFG_OFFSET_MOBILE);
        if ($offset < 0 || $offset > self::OFFSET_MAX) {
            return $this->displayError($this->trans('Enter the distance on phones as a number from 0 to 200. Nothing was saved.', [], 'Modules.M4pcontactwidget.Admin'));
        }
        foreach (Language::getLanguages(false) as $language) {
            $privacyUrl = trim((string) Tools::getValue(self::CFG_PRIVACY_URL . '_' . (int) $language['id_lang']));
            if ($privacyUrl !== '' && !Validate::isAbsoluteUrl($privacyUrl)) {
                return $this->displayError($this->trans('Enter the privacy policy address in full, starting with https://, or leave it empty. Nothing was saved.', [], 'Modules.M4pcontactwidget.Admin'));
            }
        }
        $hiddenPages = [];
        foreach (array_keys(self::PAGE_GROUPS) as $group) {
            if (Tools::getValue(self::CFG_HIDDEN_PAGES . '_' . $group)) {
                $hiddenPages[] = $group;
            }
        }

        Configuration::updateValue(self::CFG_PRIVACY_CMS_ID, (int) Tools::getValue(self::CFG_PRIVACY_CMS_ID));
        Configuration::updateValue(self::CFG_NOTIFY_EMAIL, $notifyEmail);
        Configuration::updateValue(self::CFG_RETENTION_DAYS, $retention);
        Configuration::updateValue(self::CFG_PHONE, trim((string) Tools::getValue(self::CFG_PHONE)));
        Configuration::updateValue(self::CFG_EMAIL, $email);
        Configuration::updateValue(self::CFG_MODULE_CSS, (int) (bool) Tools::getValue(self::CFG_MODULE_CSS));
        Configuration::updateValue(self::CFG_SCHEDULE, $week ? (string) json_encode($week) : '');
        Configuration::updateValue(self::CFG_EXCEPTIONS, $exceptions);
        Configuration::updateValue(self::CFG_OFFSET_MOBILE, $offset);
        Configuration::updateValue(self::CFG_AVOID, trim(str_replace(['<', '>', '{', '}', ';'], '', (string) Tools::getValue(self::CFG_AVOID))));
        Configuration::updateValue(self::CFG_HIDDEN_PAGES, implode(',', $hiddenPages));
        foreach (self::SWITCH_KEYS as $key) {
            Configuration::updateValue($key, (int) (bool) Tools::getValue($key));
        }
        foreach (self::LANG_KEYS as $key) {
            $values = [];
            foreach (Language::getLanguages(false) as $language) {
                $values[(int) $language['id_lang']] = trim((string) Tools::getValue($key . '_' . (int) $language['id_lang']));
            }
            Configuration::updateValue($key, $values);
        }

        return $this->displayConfirmation($this->trans('Settings updated.', [], 'Modules.M4pcontactwidget.Admin'));
    }

    private function renderConfigForm(): string
    {
        $t = function (string $text): string {
            return $this->trans($text, [], 'Modules.M4pcontactwidget.Admin');
        };

        $cmsOptions = [['id' => 0, 'name' => $t('None (no link)')]];
        $cmsPages = Db::getInstance()->executeS(
            'SELECT c.id_cms, cl.meta_title FROM `' . _DB_PREFIX_ . 'cms` c
             JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON cl.id_cms = c.id_cms AND cl.id_lang = ' . (int) $this->context->language->id . '
             ORDER BY cl.meta_title'
        ) ?: [];
        foreach ($cmsPages as $page) {
            $cmsOptions[] = ['id' => (int) $page['id_cms'], 'name' => $page['meta_title']];
        }

        $hourInputs = [];
        foreach (range(1, 7) as $day) {
            $hourInputs[] = [
                'type' => 'text',
                'label' => $this->longDay($day),
                'name' => 'm4p_hours_' . $day,
                'class' => 'fixed-width-lg',
                'placeholder' => $t('Closed'),
                'desc' => $day === 7 ? $t('From–to, e.g. 7:30-16:00. Empty: closed that day. With no day filled in, the button shows no status and the panel shows the opening hours text instead. The hours of the sales department in M4P Core replace these when set.') : '',
            ];
        }

        $inputs = [
            ['type' => 'text', 'label' => $t('Button label'), 'name' => self::CFG_LABEL, 'lang' => true, 'class' => 'fixed-width-xl'],
            $this->switchInput(self::CFG_FAB_STATUS, $t('Show the hours on the button'), $t('Open now or the next opening time next to the label, with the status dot. Off: the label alone.')),
            ['type' => 'text', 'label' => $t('Heading'), 'name' => self::CFG_TITLE, 'lang' => true],
            ['type' => 'textarea', 'label' => $t('Text under the heading'), 'name' => self::CFG_INTRO, 'lang' => true],
            $this->switchInput(self::CFG_SHOW_PHONE, $t('Show the phone')),
            ['type' => 'text', 'label' => $t('Phone'), 'name' => self::CFG_PHONE, 'desc' => $t('Shown as written; the link dials the digits. Empty hides the row. The phone of the sales department in M4P Core replaces it when set.')],
            $this->switchInput(self::CFG_SHOW_EMAIL, $t('Show the e-mail')),
            ['type' => 'text', 'label' => $t('E-mail'), 'name' => self::CFG_EMAIL, 'desc' => $t('Empty hides the row.')],
            ['type' => 'text', 'label' => $t('Opening hours text'), 'name' => self::CFG_HOURS, 'lang' => true, 'desc' => $t('Shown only when no weekly hours are set below. Empty hides the row.')],
            [
                'type' => 'checkbox',
                'label' => $t('Hide the widget on'),
                'name' => self::CFG_HIDDEN_PAGES,
                'values' => ['query' => $this->pageGroupOptions(), 'id' => 'id', 'name' => 'name'],
            ],
            ['type' => 'text', 'label' => $t('Speech bubble'), 'name' => self::CFG_BUBBLE, 'lang' => true, 'desc' => $t('Shown next to the button once per visit, after the visitor scrolls past the first screen. Empty turns it off.')],
        ];

        $formInputs = [
            $this->switchInput(self::CFG_FORM_PHONE, $t('Call-back by phone'), $t('The visitor leaves a phone number.')),
            $this->switchInput(self::CFG_FORM_EMAIL, $t('Reply by e-mail'), $t('The visitor leaves an e-mail address. With both options off the form is hidden.')),
            ['type' => 'text', 'label' => $t('Form heading for a call-back'), 'name' => self::CFG_FORM_TITLE_PHONE, 'lang' => true],
            ['type' => 'text', 'label' => $t('Button for a call-back'), 'name' => self::CFG_SUBMIT_PHONE, 'lang' => true, 'class' => 'fixed-width-xl'],
            ['type' => 'text', 'label' => $t('Form heading for an e-mail reply'), 'name' => self::CFG_FORM_TITLE_EMAIL, 'lang' => true],
            ['type' => 'text', 'label' => $t('Button for an e-mail reply'), 'name' => self::CFG_SUBMIT_EMAIL, 'lang' => true, 'class' => 'fixed-width-xl'],
            ['type' => 'text', 'label' => $t('Call-back note during opening hours'), 'name' => self::CFG_CALLBACK_OPEN, 'lang' => true, 'desc' => $t('Under the form heading while the desk is open, e.g. when you call back.')],
            ['type' => 'text', 'label' => $t('Call-back note outside opening hours'), 'name' => self::CFG_CALLBACK_CLOSED, 'lang' => true],
            ['type' => 'textarea', 'label' => $t('Consent text'), 'name' => self::CFG_CONSENT, 'lang' => true, 'rows' => 3, 'desc' => $t('Next to the checkbox the visitor has to tick before sending.')],
            ['type' => 'text', 'label' => $t('Privacy policy link text'), 'name' => self::CFG_PRIVACY_LABEL, 'lang' => true, 'class' => 'fixed-width-xl', 'desc' => $t('Shown after the consent text. Empty: no link.')],
            [
                'type' => 'select',
                'label' => $t('Privacy policy page'),
                'name' => self::CFG_PRIVACY_CMS_ID,
                'options' => ['query' => $cmsOptions, 'id' => 'id', 'name' => 'name'],
            ],
            ['type' => 'text', 'label' => $t('Privacy policy address'), 'name' => self::CFG_PRIVACY_URL, 'lang' => true, 'desc' => $t('Fill in only when the policy is not a page of the shop, e.g. https://example.com/privacy. When filled in, the link leads here instead of the page above.')],
            ['type' => 'textarea', 'label' => $t('Message after sending'), 'name' => self::CFG_SUCCESS, 'lang' => true, 'rows' => 2],
        ];

        $otherInputs = [
            [
                'type' => 'text',
                'label' => $t('Notification e-mail'),
                'name' => self::CFG_NOTIFY_EMAIL,
                'desc' => $t('Every request from the form is sent here. Empty means no e-mail; requests are still listed in the back office.'),
            ],
            [
                'type' => 'text',
                'label' => $t('Keep submissions for'),
                'name' => self::CFG_RETENTION_DAYS,
                'suffix' => $t('days'),
                'class' => 'fixed-width-sm',
                'desc' => $t('Older submissions are deleted once a day. Enter 0 to keep them until you delete them yourself.'),
            ],
            [
                'type' => 'text',
                'label' => $t('Distance from the bottom on phones'),
                'name' => self::CFG_OFFSET_MOBILE,
                'suffix' => 'px',
                'class' => 'fixed-width-sm',
                'desc' => $t('Lifts the button above bars fixed to the bottom of the screen, from 0 to 200. Themes can also set the CSS variable --m4p-contact-offset on a page.'),
            ],
            [
                'type' => 'text',
                'label' => $t('Elements the button does not cover'),
                'name' => self::CFG_AVOID,
                'desc' => $t('CSS selectors separated by commas, e.g. the slider controls or the cookie banner. While the button would cover one of them, it hides until the page is scrolled.'),
            ],
            [
                'type' => 'switch',
                'label' => $t('Load the module stylesheet'),
                'name' => self::CFG_MODULE_CSS,
                'is_bool' => true,
                'values' => [
                    ['id' => 'css_on', 'value' => 1, 'label' => $t('Yes')],
                    ['id' => 'css_off', 'value' => 0, 'label' => $t('No')],
                ],
                'desc' => $t('Turn it off when the theme styles the widget itself.'),
            ],
        ];

        $fields_form = [
            'form' => [
                'legend' => ['title' => $t('Contact widget'), 'icon' => 'icon-cogs'],
                'description' => Shop::isFeatureActive() ? $t('The settings belong to the shop selected above.') : '',
                'input' => $inputs,
                'submit' => ['title' => $t('Save'), 'name' => 'submitM4pContactWidget'],
            ],
        ];
        $callback_form = [
            'form' => [
                'legend' => ['title' => $t('Call-back form'), 'icon' => 'icon-envelope'],
                'input' => $formInputs,
                'submit' => ['title' => $t('Save'), 'name' => 'submitM4pContactWidget'],
            ],
        ];
        $other_form = [
            'form' => [
                'legend' => ['title' => $t('Notifications and display'), 'icon' => 'icon-cogs'],
                'input' => $otherInputs,
                'submit' => ['title' => $t('Save'), 'name' => 'submitM4pContactWidget'],
            ],
        ];
        $hours_form = [
            'form' => [
                'legend' => ['title' => $t('Opening hours'), 'icon' => 'icon-time'],
                'description' => $t('The button shows whether the desk is open now, counted in the shop time zone.'),
                'input' => array_merge($hourInputs, [[
                    'type' => 'textarea',
                    'label' => $t('Exceptions'),
                    'name' => self::CFG_EXCEPTIONS,
                    'rows' => 4,
                    'desc' => $t('One date per line, e.g. 2026-12-24 8:00-12:00 or 2026-12-25 closed. The next two weeks show in the panel.'),
                ]]),
                'submit' => ['title' => $t('Save'), 'name' => 'submitM4pContactWidget'],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitM4pContactWidget';
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->languages = $this->context->controller->getLanguages();
        $helper->fields_value = [
            self::CFG_PRIVACY_CMS_ID => (int) Configuration::get(self::CFG_PRIVACY_CMS_ID),
            self::CFG_NOTIFY_EMAIL => (string) Configuration::get(self::CFG_NOTIFY_EMAIL),
            self::CFG_RETENTION_DAYS => (int) Configuration::get(self::CFG_RETENTION_DAYS),
            self::CFG_PHONE => (string) Configuration::get(self::CFG_PHONE),
            self::CFG_EMAIL => (string) Configuration::get(self::CFG_EMAIL),
            self::CFG_MODULE_CSS => (int) Configuration::get(self::CFG_MODULE_CSS),
            self::CFG_OFFSET_MOBILE => (int) Configuration::get(self::CFG_OFFSET_MOBILE),
            self::CFG_AVOID => (string) Configuration::get(self::CFG_AVOID),
            self::CFG_EXCEPTIONS => (string) Configuration::get(self::CFG_EXCEPTIONS),
        ];
        foreach (self::SWITCH_KEYS as $key) {
            $helper->fields_value[$key] = (int) Configuration::get($key);
        }
        $hiddenPages = $this->hiddenPages();
        foreach (array_keys(self::PAGE_GROUPS) as $group) {
            $helper->fields_value[self::CFG_HIDDEN_PAGES . '_' . $group] = in_array($group, $hiddenPages, true);
        }
        foreach (M4pContactSchedule::parseWeek((string) Configuration::get(self::CFG_SCHEDULE)) as $day => $range) {
            $helper->fields_value['m4p_hours_' . $day] = $range ? $this->displayTime($range[0]) . '-' . $this->displayTime($range[1]) : '';
        }
        foreach (self::LANG_KEYS as $key) {
            foreach (Language::getLanguages(false) as $language) {
                $helper->fields_value[$key][(int) $language['id_lang']] = (string) Configuration::get($key, (int) $language['id_lang']);
            }
        }

        return $helper->generateForm([$fields_form, $callback_form, $hours_form, $other_form]);
    }

    /**
     * @return array<string, mixed>
     */
    private function switchInput(string $name, string $label, string $desc = ''): array
    {
        return [
            'type' => 'switch',
            'label' => $label,
            'name' => $name,
            'is_bool' => true,
            'values' => [
                ['id' => $name . '_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.M4pcontactwidget.Admin')],
                ['id' => $name . '_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.M4pcontactwidget.Admin')],
            ],
            'desc' => $desc,
        ];
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function pageGroupOptions(): array
    {
        $labels = [
            'index' => $this->trans('Home page', [], 'Modules.M4pcontactwidget.Admin'),
            'category' => $this->trans('Category pages', [], 'Modules.M4pcontactwidget.Admin'),
            'product' => $this->trans('Product pages', [], 'Modules.M4pcontactwidget.Admin'),
            'cms' => $this->trans('Content pages (CMS)', [], 'Modules.M4pcontactwidget.Admin'),
            'search' => $this->trans('Search results', [], 'Modules.M4pcontactwidget.Admin'),
            'brands' => $this->trans('Brands and suppliers', [], 'Modules.M4pcontactwidget.Admin'),
            'listings' => $this->trans('New products, best sellers and price drops', [], 'Modules.M4pcontactwidget.Admin'),
            'contact' => $this->trans('Contact page and our stores', [], 'Modules.M4pcontactwidget.Admin'),
            'cart' => $this->trans('Cart', [], 'Modules.M4pcontactwidget.Admin'),
            'order' => $this->trans('Checkout', [], 'Modules.M4pcontactwidget.Admin'),
            'order_confirmation' => $this->trans('Order confirmation', [], 'Modules.M4pcontactwidget.Admin'),
            'login' => $this->trans('Log in, registration and password reset', [], 'Modules.M4pcontactwidget.Admin'),
            'account' => $this->trans('Customer account', [], 'Modules.M4pcontactwidget.Admin'),
            'other' => $this->trans('Other pages (e.g. blog, 404 page, sitemap)', [], 'Modules.M4pcontactwidget.Admin'),
        ];
        $options = [];
        foreach (array_keys(self::PAGE_GROUPS) as $group) {
            $options[] = ['id' => $group, 'name' => $labels[$group]];
        }

        return $options;
    }
}
