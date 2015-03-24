<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;

interface AccountFactoryInterface
{
    /**
     * Creates a new empty account
     * @return AccountInterface
     */
    public function create();

    /**
     * Creates a new api entity out of an account
     * @param AccountInterface $account
     * @param string $locale
     * @return Account
     */
    public function createApiEntity(AccountInterface $account, $locale);
}
