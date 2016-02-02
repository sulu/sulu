<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Sulu\Bundle\ContactBundle\Api\Account as AccountApi;
use Sulu\Bundle\ContactBundle\Entity\Account as AccountEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;

/**
 * factory to encapsulate account creation.
 */
class AccountFactory implements AccountFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createEntity()
    {
        return new AccountEntity();
    }

    /**
     * {@inheritdoc}
     */
    public function createApiEntity(AccountInterface $account, $locale)
    {
        return new AccountApi($account, $locale);
    }
}
