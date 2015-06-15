<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\ContactBundle;

use Sulu\Bundle\PersistenceBundle\PersistenceBundleInterface;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluContactBundle extends Bundle implements PersistenceBundleInterface
{
    use PersistenceBundleTrait;

    /**
     * Target entities resolver configuration.
     * Mapping a interface to a concrete implementation.
     *
     * @return array
     */
    protected function getModelInterfaces()
    {
        return array(
            'Sulu\Component\Contact\Model\ContactInterface' => 'sulu.model.contact.class',
        );
    }
}
