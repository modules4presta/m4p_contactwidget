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
 * A "call me back" request sent from the widget; the model behind the back-office list and its deletion.
 */
class M4pContactRequest extends ObjectModel
{
    /** @var string phone|email */
    public $channel;

    /** @var string */
    public $contact_value;

    /** @var bool */
    public $consent;

    /** @var int */
    public $id_shop;

    /** @var string */
    public $ip_address;

    /** @var string */
    public $page_url;

    /** @var string */
    public $date_add;

    public static $definition = [
        'table' => 'm4p_contact_request',
        'primary' => 'id_m4p_contact_request',
        'fields' => [
            'channel' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 5],
            'contact_value' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 191],
            'consent' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'ip_address' => ['type' => self::TYPE_STRING, 'size' => 45],
            'page_url' => ['type' => self::TYPE_STRING, 'size' => 512],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
