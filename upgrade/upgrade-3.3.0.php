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
 * Adds the channel switches, the list of pages without the widget and the form texts, with values that keep
 * the widget as it was: every channel on, hidden in the cart and the checkout, the texts it showed so far.
 *
 * @param M4p_ContactWidget $module
 *
 * @return bool
 */
function upgrade_module_3_3_0($module)
{
    $module->applyDefaults();

    return true;
}
