<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfig;
use Sulu\Bundle\ContactBundle\Entity\Account;

class SuluContactJsConfig extends JsConfig
{

    protected $name = 'sulu-contact';

    public function __construct($bundleName = '', $params = array())
    {

        $this->parameters = array(
            'accountTypes' => array(
                array(
                    'id' => Account::TYPE_BASIC,
                    'name'=>'basic',
                    'translation' => Account::$TYPE_TRANSLATIONS[Account::TYPE_BASIC]
                ),
                array(
                    'id' => Account::TYPE_LEAD,
                    'name'=>'lead',
                    'translation' => Account::$TYPE_TRANSLATIONS[Account::TYPE_LEAD]
                ),
                array(
                    'id' => Account::TYPE_CUSTOMER,
                    'name'=>'customer',
                    'translation' => Account::$TYPE_TRANSLATIONS[Account::TYPE_CUSTOMER]
                ),
                array(
                    'id' => Account::TYPE_SUPPLIER,
                    'name'=>'supplier',
                    'translation' => Account::$TYPE_TRANSLATIONS[Account::TYPE_SUPPLIER]
                ),
            )
        );

        $this->parameters = array_merge($this->parameters, $params);

    }
}
