<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

/**
 * Account DataProvider for SmartContent.
 */
class AccountDataProvider extends BaseDataProvider
{
    public function __construct(DataProviderRepositoryInterface $repository, ArraySerializerInterface $serializer, ReferenceStoreInterface $referenceStore)
    {
        parent::__construct($repository, $serializer, $referenceStore);

        $this->configuration = self::createConfigurationBuilder()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableView(ContactAdmin::ACCOUNT_EDIT_FORM_VIEW, ['id' => 'id'])
            ->getConfiguration();
    }

    protected function decorateDataItems(array $data)
    {
        return array_map(
            function($item) {
                return new AccountDataItem($item);
            },
            $data
        );
    }

    protected function getSerializationContext()
    {
        return parent::getSerializationContext()->setGroups(['fullAccount', 'partialContact', 'partialCategory']);
    }
}
