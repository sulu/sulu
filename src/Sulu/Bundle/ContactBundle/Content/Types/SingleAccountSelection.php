<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Content\Types;

use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleAccountSelection extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var AccountManager
     */
    protected $accountManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $accountReferenceStore;

    public function __construct(
        AccountManager $accountManager,
        ReferenceStoreInterface $accountReferenceStore
    ) {
        $this->accountManager = $accountManager;
        $this->accountReferenceStore = $accountReferenceStore;

        parent::__construct('SingleAccount');
    }

    public function getContentData(PropertyInterface $property): ?Account
    {
        $id = $property->getValue();

        if (!$id) {
            return null;
        }

        return $this->accountManager->getById($id, $property->getStructure()->getLanguageCode());
    }

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        $id = $property->getValue();
        if (!$id) {
            return;
        }

        $this->accountReferenceStore->add($id);
    }
}
