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

require_once _PS_MODULE_DIR_ . 'm4p_contactwidget/classes/M4pContactRequest.php';

/**
 * Customers > Contact widget requests: the requests sent from the widget, newest first; read-only
 * except for deleting (e.g. on a GDPR request).
 *
 * @property M4p_ContactWidget $module
 */
class AdminM4pContactRequestsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'm4p_contact_request';
        $this->className = 'M4pContactRequest';
        $this->identifier = 'id_m4p_contact_request';
        $this->list_no_link = true;
        $this->allow_export = true;
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $shopIds = array_map('intval', Shop::getContextListShopID()) ?: [(int) $this->context->shop->id];
        $this->_where = ' AND a.`id_shop` IN (' . implode(',', $shopIds) . ')';

        $this->fields_list = [
            'id_m4p_contact_request' => ['title' => $this->t('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'date_add' => ['title' => $this->t('Date'), 'type' => 'datetime'],
            'channel' => [
                'title' => $this->t('Channel'),
                'type' => 'select',
                'list' => ['phone' => $this->t('Phone'), 'email' => $this->t('E-mail')],
                'filter_key' => 'a!channel',
                'callback' => 'displayChannel',
            ],
            'contact_value' => ['title' => $this->t('Contact'), 'callback' => 'displayContact'],
            'page_url' => ['title' => $this->t('Page'), 'callback' => 'displayPage'],
            'ip_address' => ['title' => $this->t('IP')],
        ];

        if (Shop::isFeatureActive()) {
            $this->_select = 's.`name` AS shop_name';
            $this->_join = ' LEFT JOIN `' . _DB_PREFIX_ . 'shop` s ON s.`id_shop` = a.`id_shop`';
            $this->fields_list['shop_name'] = ['title' => $this->t('Shop'), 'filter_key' => 's!name'];
        }

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->t('Delete selected'),
                'confirm' => $this->t('Delete the selected requests?'),
                'icon' => 'icon-trash',
            ],
        ];
    }

    public function renderList()
    {
        $this->module->purgeOldRows();
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function initPageHeaderToolbar(): void
    {
        parent::initPageHeaderToolbar();
        // Requests come only from the shop's widget.
        unset($this->page_header_toolbar_btn['new']);
    }

    public function initToolbar(): void
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function displayChannel(string $value): string
    {
        return htmlspecialchars($value === 'phone' ? $this->t('Phone') : $this->t('E-mail'), ENT_QUOTES);
    }

    /**
     * The phone number or e-mail as a tel:/mailto: link, so the request can be answered from the list.
     */
    public function displayContact(string $value, array $row): string
    {
        $href = ($row['channel'] === 'phone' ? 'tel:' : 'mailto:') . $value;

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '"><strong>' . htmlspecialchars($value, ENT_QUOTES) . '</strong></a>';
    }

    public function displayPage(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $label = Tools::strlen($value) > 50 ? Tools::substr($value, 0, 50) . '…' : $value;

        return '<a href="' . htmlspecialchars($value, ENT_QUOTES) . '" target="_blank" rel="noopener">' . htmlspecialchars($label, ENT_QUOTES) . '</a>';
    }

    private function t(string $text): string
    {
        return $this->module->getTranslator()->trans($text, [], 'Modules.M4pcontactwidget.Admin');
    }
}
