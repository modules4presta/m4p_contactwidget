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
 * The switch for the hours on the button starts on, as the button looked before.
 */
function upgrade_module_3_5_0(Module $module): bool
{
    if (!Configuration::hasKey(M4p_ContactWidget::CFG_FAB_STATUS)) {
        Configuration::updateValue(M4p_ContactWidget::CFG_FAB_STATUS, 1);
    }

    return true;
}
