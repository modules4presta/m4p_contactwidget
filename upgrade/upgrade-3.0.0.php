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
 * Moves the widget to displayBeforeBodyClosingTag, adds the contact settings that are not stored yet,
 * and the Customers > Contact widget requests tab.
 *
 * @param M4p_ContactWidget $module
 *
 * @return bool
 */
function upgrade_module_3_0_0($module)
{
    $module->applyDefaults();
    $module->unregisterHook('displayFooter');

    return $module->installTab() && $module->registerHooks();
}
