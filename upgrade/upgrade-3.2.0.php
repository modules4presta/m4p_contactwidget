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
 * Adds the button label, the call-back notes, the weekly hours, the exceptions and the distance on phones.
 * The weekly hours start empty, so the panel keeps showing the opening hours text until they are filled in.
 *
 * @param M4p_ContactWidget $module
 *
 * @return bool
 */
function upgrade_module_3_2_0($module)
{
    $module->applyDefaults();

    return true;
}
