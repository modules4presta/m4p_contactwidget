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
 * Floating "request a callback" widget: a button in the corner of the shop
 * opening a short phone or e-mail form with a consent checkbox.
 *
 * Self-contained — own table, own endpoint — so it depends on no other module.
 */
class M4p_ContactWidget extends Module
{
    public const TABLE = 'm4p_contact_request';
    public const CFG_PRIVACY_CMS_ID = 'M4P_CONTACTWIDGET_PRIVACY_CMS_ID';
    public const CFG_NOTIFY_EMAIL = 'M4P_CONTACTWIDGET_NOTIFY_EMAIL';
    public const CFG_RETENTION_DAYS = 'M4P_CONTACTWIDGET_RETENTION_DAYS';
    public const CFG_LAST_PURGE = 'M4P_CONTACTWIDGET_LAST_PURGE';

    /** Name of the cookie entry holding the per-visitor form secret. */
    public const COOKIE_NONCE = 'm4p_contact_nonce';

    private const CFG_KEYS = [
        self::CFG_PRIVACY_CMS_ID,
        self::CFG_NOTIFY_EMAIL,
        self::CFG_RETENTION_DAYS,
        self::CFG_LAST_PURGE,
    ];

    /** Rows kept per shop unless the setting says otherwise. */
    private const DEFAULT_RETENTION_DAYS = 365;

    /** At most one purge per shop in this many seconds. */
    private const PURGE_INTERVAL = 86400;

    public function __construct()
    {
        $this->name = 'm4p_contactwidget';
        $this->tab = 'front_office_features';
        $this->version = '2.1.0';
        $this->author = 'Modules4Presta';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Callback request widget', [], 'Modules.M4pcontactwidget.Admin');
        $this->description = $this->trans('A button in the corner of the shop that lets visitors leave a phone number or an e-mail address for you to call back.', [], 'Modules.M4pcontactwidget.Admin');
        $this->confirmUninstall = $this->trans('Remove the module? The submissions collected so far are deleted with it.', [], 'Modules.M4pcontactwidget.Admin');
    }

    public function install(): bool
    {
        // Best-effort default: point at the CMS page that looks like the
        // privacy policy, so the link works out of the box; admin can
        // change it in Configure without touching code.
        $guessedCmsId = (int) Db::getInstance()->getValue(
            'SELECT c.id_cms FROM `' . _DB_PREFIX_ . 'cms` c
             JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON cl.id_cms = c.id_cms
             WHERE cl.link_rewrite LIKE \'%polityka-prywatnosci%\' OR cl.link_rewrite LIKE \'%privacy%\''
        );

        Configuration::updateValue(self::CFG_PRIVACY_CMS_ID, $guessedCmsId);
        Configuration::updateValue(self::CFG_NOTIFY_EMAIL, Configuration::get('PS_SHOP_EMAIL'));
        Configuration::updateValue(self::CFG_RETENTION_DAYS, self::DEFAULT_RETENTION_DAYS);

        return parent::install()
            && $this->installDb()
            && $this->registerHook('displayFooter')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionExportGDPRData')
            && $this->registerHook('actionDeleteGDPRCustomer')
            && $this->registerHook('actionObjectShopDeleteAfter');
    }

    public function uninstall(): bool
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE . '`');

        foreach (self::CFG_KEYS as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    public function installDb(): bool
    {
        // ip_date carries the per-address rate check and shop_date both the
        // back-office list and the retention purge, which run on every
        // submission and would otherwise scan the whole table.
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
     * The secret behind the form token. Written while the page is still being
     * built, because Cookie::write() does nothing once output has started.
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
     * Token the form carries: tied to the visitor's own cookie, so it is
     * neither guessable nor reusable from another browser.
     */
    public static function formToken(Cookie $cookie): string
    {
        $nonce = (string) $cookie->{self::COOKIE_NONCE};

        return $nonce === '' ? '' : Tools::hash($nonce . 'm4p_contactwidget');
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionFrontControllerSetMedia(array $params): void
    {
        self::issueNonce($this->context->cookie);
        $this->purgeOldRows();

        $this->context->controller->registerStylesheet(
            'm4p-contactwidget',
            'modules/' . $this->name . '/views/css/widget.css',
            ['media' => 'all', 'priority' => 1000]
        );
        $this->context->controller->registerJavascript(
            'm4p-contactwidget',
            'modules/' . $this->name . '/views/js/widget.js',
            ['position' => 'bottom', 'priority' => 200]
        );

        Media::addJsDef([
            'm4pContactWidgetUrl' => $this->context->link->getModuleLink($this->name, 'submit', [], true),
            'm4pContactWidgetPlaceholders' => [
                'phone' => $this->trans('e.g. +48 123 456 789', [], 'Modules.M4pcontactwidget.Shop'),
                'email' => $this->trans('e.g. jan.kowalski@company.com', [], 'Modules.M4pcontactwidget.Shop'),
            ],
            'm4pContactWidgetMessages' => [
                'consent' => $this->trans('Consent to processing your data is required.', [], 'Modules.M4pcontactwidget.Shop'),
                'email' => $this->trans('Enter a valid e-mail address.', [], 'Modules.M4pcontactwidget.Shop'),
                'phone' => $this->trans('Enter a valid phone number.', [], 'Modules.M4pcontactwidget.Shop'),
                'success' => $this->trans('Thank you, we will get back to you as soon as we can.', [], 'Modules.M4pcontactwidget.Shop'),
                'failure' => $this->trans('The request could not be sent. Please try again.', [], 'Modules.M4pcontactwidget.Shop'),
                'network' => $this->trans('Connection problem. Please try again.', [], 'Modules.M4pcontactwidget.Shop'),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookDisplayFooter(array $params): string
    {
        $privacyUrl = '';
        $idCms = (int) Configuration::get(self::CFG_PRIVACY_CMS_ID);
        if ($idCms > 0) {
            $privacyUrl = $this->context->link->getCMSLink($idCms);
        }

        $this->context->smarty->assign([
            'm4pPrivacyUrl' => $privacyUrl,
            'm4pContactToken' => self::formToken($this->context->cookie),
        ]);

        return $this->display(__FILE__, 'widget.tpl');
    }

    /**
     * Rows older than the retention setting of the shop they belong to.
     * Called from the front office and from the configuration page, at most
     * once a day per shop.
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

        // The cut-off is computed in PHP, the same clock the rows were written
        // with, so the retention period does not shift with the time zone the
        // database session runs in.
        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE `id_shop` = ' . (int) $this->context->shop->id . '
               AND `date_add` < \'' . pSQL(date('Y-m-d H:i:s', time() - ($days * 86400))) . '\''
        );

        Configuration::updateValue(self::CFG_LAST_PURGE, time());
    }

    /**
     * Submissions the psgdpr module exports for a customer, matched on the
     * e-mail address they left in the widget.
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
     * Submissions belong to the shop they were left in, so a shop that is
     * removed takes them with it.
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
        $output = $this->handleActions();
        $this->purgeOldRows();

        return $output . $this->renderConfigForm() . $this->renderRequestsList();
    }

    private function handleActions(): string
    {
        $idDelete = (int) Tools::getValue('m4p_delete_request');
        if ($idDelete > 0) {
            if (!hash_equals(Tools::getAdminTokenLite('AdminModules'), (string) Tools::getValue('token'))) {
                return $this->displayError($this->trans('Invalid security token.', [], 'Modules.M4pcontactwidget.Admin'));
            }

            Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . self::TABLE . '`
                 WHERE `id_m4p_contact_request` = ' . $idDelete . '
                   AND `id_shop` IN (' . $this->contextShopIds() . ')'
            );

            return $this->displayConfirmation($this->trans('Submission deleted.', [], 'Modules.M4pcontactwidget.Admin'));
        }

        if (!Tools::isSubmit('submitM4pContactWidget')) {
            return '';
        }

        $idCms = (int) Tools::getValue(self::CFG_PRIVACY_CMS_ID);
        $notifyEmail = trim((string) Tools::getValue(self::CFG_NOTIFY_EMAIL));
        $retention = (int) Tools::getValue(self::CFG_RETENTION_DAYS);

        if ($notifyEmail !== '' && !Validate::isEmail($notifyEmail)) {
            return $this->displayError($this->trans('Enter a valid e-mail address for notifications.', [], 'Modules.M4pcontactwidget.Admin'));
        }

        if ($retention < 0) {
            return $this->displayError($this->trans('Enter the number of days as 0 or more.', [], 'Modules.M4pcontactwidget.Admin'));
        }

        Configuration::updateValue(self::CFG_PRIVACY_CMS_ID, $idCms);
        Configuration::updateValue(self::CFG_NOTIFY_EMAIL, $notifyEmail);
        Configuration::updateValue(self::CFG_RETENTION_DAYS, $retention);

        return $this->displayConfirmation($this->trans('Settings saved.', [], 'Modules.M4pcontactwidget.Admin'));
    }

    /**
     * Shops the employee is looking at, as a list for an IN clause.
     */
    private function contextShopIds(): string
    {
        $ids = array_map('intval', Shop::getContextListShopID());

        return implode(',', $ids ?: [(int) $this->context->shop->id]);
    }

    private function renderConfigForm(): string
    {
        $cmsOptions = [['id' => 0, 'name' => $this->trans('– none, hide the link –', [], 'Modules.M4pcontactwidget.Admin')]];
        $cmsPages = Db::getInstance()->executeS(
            'SELECT c.id_cms, cl.meta_title FROM `' . _DB_PREFIX_ . 'cms` c
             JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON cl.id_cms = c.id_cms AND cl.id_lang = ' . (int) $this->context->language->id . '
             ORDER BY cl.meta_title'
        );
        foreach ($cmsPages as $page) {
            $cmsOptions[] = ['id' => (int) $page['id_cms'], 'name' => $page['meta_title']];
        }

        $inputs = [
            [
                'type' => 'select',
                'label' => $this->trans('Privacy policy page', [], 'Modules.M4pcontactwidget.Admin'),
                'name' => self::CFG_PRIVACY_CMS_ID,
                'desc' => $this->trans('The consent text in the widget links to the page you pick here.', [], 'Modules.M4pcontactwidget.Admin'),
                'options' => [
                    'query' => $cmsOptions,
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->trans('Notify this address', [], 'Modules.M4pcontactwidget.Admin'),
                'name' => self::CFG_NOTIFY_EMAIL,
                'desc' => $this->trans('Every submission is e-mailed here. Leave it empty to send nothing – submissions still appear in the list below.', [], 'Modules.M4pcontactwidget.Admin'),
            ],
            [
                'type' => 'text',
                'label' => $this->trans('Keep submissions for', [], 'Modules.M4pcontactwidget.Admin'),
                'name' => self::CFG_RETENTION_DAYS,
                'suffix' => $this->trans('days', [], 'Modules.M4pcontactwidget.Admin'),
                'class' => 'fixed-width-sm',
                'desc' => $this->trans('Older submissions are deleted once a day. Enter 0 to keep them until you delete them yourself.', [], 'Modules.M4pcontactwidget.Admin'),
            ],
        ];

        if (Shop::isFeatureActive()) {
            $inputs[0]['desc'] .= ' ' . $this->trans('The three settings belong to the shop selected above.', [], 'Modules.M4pcontactwidget.Admin');
        }

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->trans('Settings', [], 'Modules.M4pcontactwidget.Admin'), 'icon' => 'icon-cogs'],
                'input' => $inputs,
                'submit' => ['title' => $this->trans('Save', [], 'Modules.M4pcontactwidget.Admin'), 'name' => 'submitM4pContactWidget'],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitM4pContactWidget';
        $helper->fields_value = [
            self::CFG_PRIVACY_CMS_ID => (int) Configuration::get(self::CFG_PRIVACY_CMS_ID),
            self::CFG_NOTIFY_EMAIL => Configuration::get(self::CFG_NOTIFY_EMAIL),
            self::CFG_RETENTION_DAYS => (int) Configuration::get(self::CFG_RETENTION_DAYS),
        ];

        return $helper->generateForm([$fields_form]);
    }

    private function renderRequestsList(): string
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE `id_shop` IN (' . $this->contextShopIds() . ')
             ORDER BY `date_add` DESC
             LIMIT 100'
        ) ?: [];

        $multishop = Shop::isFeatureActive();
        $shopNames = [];
        if ($multishop) {
            foreach (Shop::getShops(false) as $shop) {
                $shopNames[(int) $shop['id_shop']] = $shop['name'];
            }
        }

        $baseUrl = AdminController::$currentIndex . '&configure=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules');

        foreach ($rows as &$row) {
            $row['shop_name'] = $shopNames[(int) $row['id_shop']] ?? '';
            $row['url_delete'] = $baseUrl . '&m4p_delete_request=' . (int) $row['id_m4p_contact_request'];
        }
        unset($row);

        $this->context->smarty->assign([
            'm4pRequests' => $rows,
            'm4pMultistore' => $multishop,
        ]);

        return $this->display(__FILE__, 'requests-list.tpl');
    }
}
