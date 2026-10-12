<?php

/**
 * m4p_contactwidget
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Adds the indexes the rate checks and the list read, the retention setting,
 * the two hooks the psgdpr module calls and the one that clears the
 * submissions of a deleted shop.
 *
 * @param Module $module
 *
 * @return bool
 */
function upgrade_module_2_1_0($module)
{
    $table = _DB_PREFIX_ . M4p_ContactWidget::TABLE;
    $indexes = [
        'ip_date' => '`ip_address`, `date_add`',
        'shop_date' => '`id_shop`, `date_add`',
    ];

    foreach ($indexes as $name => $columns) {
        $exists = Db::getInstance()->getRow(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = \'' . pSQL($name) . '\''
        );

        if (!$exists && !Db::getInstance()->execute('ALTER TABLE `' . $table . '` ADD KEY `' . $name . '` (' . $columns . ')')) {
            return false;
        }
    }

    if (Configuration::get(M4p_ContactWidget::CFG_RETENTION_DAYS) === false) {
        Configuration::updateValue(M4p_ContactWidget::CFG_RETENTION_DAYS, 365);
    }

    return $module->registerHook('actionExportGDPRData')
        && $module->registerHook('actionDeleteGDPRCustomer')
        && $module->registerHook('actionObjectShopDeleteAfter');
}
