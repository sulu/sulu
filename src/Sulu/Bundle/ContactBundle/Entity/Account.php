<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

/**
 * Account
 */
class Account extends AbstractAccount
{
    const TYPE_BASIC = 0;
    const TYPE_LEAD = 1;
    const TYPE_CUSTOMER = 2;
    const TYPE_SUPPLIER = 3;

    // default value
    protected $type = self::TYPE_BASIC;
}
