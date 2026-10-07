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

    public function __construct()
    {
        $this->name = 'm4p_contactwidget';
        $this->tab = 'front_office_features';
        $this->version = '2.0.0';
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

        return parent::install()
            && $this->installDb()
            && $this->registerHook('displayFooter')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    public function uninstall(): bool
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE . '`');
        Configuration::deleteByName(self::CFG_PRIVACY_CMS_ID);
        Configuration::deleteByName(self::CFG_NOTIFY_EMAIL);

        return parent::uninstall();
    }

    private function installDb(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
            `id_m4p_contact_request` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `channel` ENUM(\'phone\',\'email\') NOT NULL,
            `contact_value` VARCHAR(191) NOT NULL,
            `consent` TINYINT(1) NOT NULL DEFAULT 0,
            `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
            `ip_address` VARCHAR(45) NOT NULL DEFAULT \'\',
            `page_url` VARCHAR(512) NOT NULL DEFAULT \'\',
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_m4p_contact_request`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionFrontControllerSetMedia(array $params): void
    {
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
        ]);
    }

    public function hookDisplayFooter(array $params): string
    {
        $privacyUrl = '';
        $idCms = (int) Configuration::get(self::CFG_PRIVACY_CMS_ID);
        if ($idCms > 0) {
            $privacyUrl = $this->context->link->getCMSLink($idCms);
        }

        $this->context->smarty->assign('m4pPrivacyUrl', $privacyUrl);

        return $this->display(__FILE__, 'widget.tpl');
    }

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submitM4pContactWidget')) {
            $idCms = (int) Tools::getValue(self::CFG_PRIVACY_CMS_ID);
            $notifyEmail = trim((string) Tools::getValue(self::CFG_NOTIFY_EMAIL));

            if ($notifyEmail !== '' && !Validate::isEmail($notifyEmail)) {
                $output .= $this->displayError($this->trans('Enter a valid e-mail address for notifications.', [], 'Modules.M4pcontactwidget.Admin'));
            } else {
                Configuration::updateValue(self::CFG_PRIVACY_CMS_ID, $idCms);
                Configuration::updateValue(self::CFG_NOTIFY_EMAIL, $notifyEmail);
                $output .= $this->displayConfirmation($this->trans('Settings saved.', [], 'Modules.M4pcontactwidget.Admin'));
            }
        }

        return $output . $this->renderConfigForm() . $this->renderRequestsList();
    }

    private function renderConfigForm(): string
    {
        $cmsOptions = [['id' => 0, 'name' => $this->trans('— none, hide the link —', [], 'Modules.M4pcontactwidget.Admin')]];
        $cmsPages = Db::getInstance()->executeS(
            'SELECT c.id_cms, cl.meta_title FROM `' . _DB_PREFIX_ . 'cms` c
             JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON cl.id_cms = c.id_cms AND cl.id_lang = ' . (int) $this->context->language->id . '
             ORDER BY cl.meta_title'
        );
        foreach ($cmsPages as $page) {
            $cmsOptions[] = ['id' => (int) $page['id_cms'], 'name' => $page['meta_title']];
        }

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->trans('Settings', [], 'Modules.M4pcontactwidget.Admin'), 'icon' => 'icon-cogs'],
                'input' => [
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
                        'desc' => $this->trans('Every submission is e-mailed here. Leave it empty to send nothing — submissions still appear in the list below.', [], 'Modules.M4pcontactwidget.Admin'),
                    ],
                ],
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
        ];

        return $helper->generateForm([$fields_form]);
    }

    private function renderRequestsList(): string
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE . '` ORDER BY date_add DESC LIMIT 100'
        );

        $this->context->smarty->assign('m4pRequests', $rows ?: []);

        return $this->display(__FILE__, 'requests-list.tpl');
    }
}
