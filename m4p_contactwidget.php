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
 * Floating contact widget in the corner of the shop: phone, e-mail and opening hours from the back
 * office, plus a "we will call you back" form (phone or e-mail with consent) stored in its own table.
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
    ];

    private const LANG_KEYS = [self::CFG_TITLE, self::CFG_INTRO, self::CFG_HOURS, self::CFG_BUBBLE];

    /** Rows kept per shop unless the setting says otherwise. */
    private const DEFAULT_RETENTION_DAYS = 365;

    /** At most one purge per shop in this many seconds. */
    private const PURGE_INTERVAL = 86400;

    /** Pages without the widget: the cart and the checkout have their own help line. */
    private const HIDDEN_ON = ['cart', 'order', 'order-confirmation'];

    private const ICONS = ['phone', 'envelope', 'clock', 'comment-dots', 'xmark', 'paper-plane', 'circle-check'];

    public function __construct()
    {
        $this->name = 'm4p_contactwidget';
        $this->tab = 'front_office_features';
        $this->version = '3.0.0';
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
        ];
        foreach ($plain as $key => $value) {
            if (!Configuration::hasKey($key)) {
                Configuration::updateValue($key, $value);
            }
        }

        $texts = [self::CFG_TITLE => [], self::CFG_INTRO => [], self::CFG_HOURS => [], self::CFG_BUBBLE => []];
        foreach (Language::getLanguages(false) as $language) {
            $id = (int) $language['id_lang'];
            $locale = (string) $language['locale'];
            $texts[self::CFG_TITLE][$id] = $this->trans('Need help?', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_INTRO][$id] = $this->trans('Call or write to us, or leave your number and we will call you back.', [], 'Modules.M4pcontactwidget.Shop', $locale);
            $texts[self::CFG_HOURS][$id] = '';
            $texts[self::CFG_BUBBLE][$id] = $this->trans('Have a question? We are happy to help.', [], 'Modules.M4pcontactwidget.Shop', $locale);
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
        return !in_array((string) ($this->context->controller->php_self ?? ''), self::HIDDEN_ON, true);
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
                ['media' => 'all', 'priority' => 1000]
            );
        }
        $this->context->controller->registerJavascript(
            'm4p-contactwidget',
            'modules/' . $this->name . '/views/js/widget.js',
            ['position' => 'bottom', 'priority' => 200]
        );
    }

    /**
     * The markup is the same for every visitor (the form token is fetched when the panel opens), so
     * full-page caches can keep it.
     */
    public function hookDisplayBeforeBodyClosingTag(): string
    {
        if (!$this->isShownHere()) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $idCms = (int) Configuration::get(self::CFG_PRIVACY_CMS_ID);
        $phone = trim((string) Configuration::get(self::CFG_PHONE));
        $email = trim((string) Configuration::get(self::CFG_EMAIL));

        $this->context->smarty->assign('m4p_contact', [
            'title' => (string) Configuration::get(self::CFG_TITLE, $idLang),
            'intro' => (string) Configuration::get(self::CFG_INTRO, $idLang),
            'hours' => (string) Configuration::get(self::CFG_HOURS, $idLang),
            'bubble' => (string) Configuration::get(self::CFG_BUBBLE, $idLang),
            'phone' => $phone,
            'phone_href' => preg_replace('/[^0-9+]/', '', $phone),
            'email' => Validate::isEmail($email) ? $email : '',
            'privacy_url' => $idCms > 0 ? $this->context->link->getCMSLink($idCms) : '',
            'submit_url' => $this->context->link->getModuleLink($this->name, 'submit', [], true),
            'icons' => $this->getIcons(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/widget.tpl');
    }

    /**
     * Font Awesome Free icons shipped in views/img, inlined as decorative SVG.
     *
     * @return array<string, string>
     */
    private function getIcons(): array
    {
        $icons = [];
        foreach (self::ICONS as $name) {
            $icons[$name] = str_replace(
                '<svg ',
                '<svg class="m4p-contact-widget__svg" aria-hidden="true" focusable="false" fill="currentColor" ',
                (string) Tools::file_get_contents(__DIR__ . '/views/img/' . $name . '.svg')
            );
        }

        return $icons;
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

        Configuration::updateValue(self::CFG_PRIVACY_CMS_ID, (int) Tools::getValue(self::CFG_PRIVACY_CMS_ID));
        Configuration::updateValue(self::CFG_NOTIFY_EMAIL, $notifyEmail);
        Configuration::updateValue(self::CFG_RETENTION_DAYS, $retention);
        Configuration::updateValue(self::CFG_PHONE, trim((string) Tools::getValue(self::CFG_PHONE)));
        Configuration::updateValue(self::CFG_EMAIL, $email);
        Configuration::updateValue(self::CFG_MODULE_CSS, (int) (bool) Tools::getValue(self::CFG_MODULE_CSS));
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

        $inputs = [
            ['type' => 'text', 'label' => $t('Heading'), 'name' => self::CFG_TITLE, 'lang' => true],
            ['type' => 'textarea', 'label' => $t('Text under the heading'), 'name' => self::CFG_INTRO, 'lang' => true],
            ['type' => 'text', 'label' => $t('Phone'), 'name' => self::CFG_PHONE, 'desc' => $t('Shown as written; the link dials the digits. Empty hides the row.')],
            ['type' => 'text', 'label' => $t('E-mail'), 'name' => self::CFG_EMAIL, 'desc' => $t('Empty hides the row.')],
            ['type' => 'text', 'label' => $t('Opening hours'), 'name' => self::CFG_HOURS, 'lang' => true, 'desc' => $t('Empty hides the row.')],
            ['type' => 'text', 'label' => $t('Speech bubble'), 'name' => self::CFG_BUBBLE, 'lang' => true, 'desc' => $t('Shown next to the button once per visitor. Empty turns it off.')],
            [
                'type' => 'select',
                'label' => $t('Privacy policy page'),
                'name' => self::CFG_PRIVACY_CMS_ID,
                'desc' => $t('Linked from the consent under the form.'),
                'options' => ['query' => $cmsOptions, 'id' => 'id', 'name' => 'name'],
            ],
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
        ];
        foreach (self::LANG_KEYS as $key) {
            foreach (Language::getLanguages(false) as $language) {
                $helper->fields_value[$key][(int) $language['id_lang']] = (string) Configuration::get($key, (int) $language['id_lang']);
            }
        }

        return $helper->generateForm([$fields_form]);
    }
}
