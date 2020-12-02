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
 * Contact DataProvider for SmartContent.
 */
class ContactDataProvider extends BaseDataProvider
{
    public function __construct(
        DataProviderRepositoryInterface $repository,
        ArraySerializerInterface $serializer,
        ReferenceStoreInterface $referenceStore
    ) {
        parent::__construct($repository, $serializer, $referenceStore);

        $this->configuration = self::createConfigurationBuilder()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableSorting(
                [
                    ['column' => 'firstName', 'title' => 'sulu_contact.first_name'],
                    ['column' => 'lastName', 'title' => 'sulu_contact.last_name'],
                ]
            )
            ->enableView(ContactAdmin::CONTACT_EDIT_FORM_VIEW, ['id' => 'id'])
            ->getConfiguration();
    }

    protected function decorateDataItems(array $data)
    {
        return \array_map(
            function($item) {
                return new ContactDataItem($item);
            },
            $data
        );
    }

    protected function getSerializationContext()
    {
        return parent::getSerializationContext()->setGroups(['fullContact', 'partialAccount', 'partialCategory']);
    }
}
