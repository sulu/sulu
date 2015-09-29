<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

/**
 * Account DataProvider for SmartContent.
 */
class AccountDataProvider extends BaseDataProvider
{
    public function __construct(DataProviderRepositoryInterface $repository)
    {
        parent::__construct($repository);

        $this->configuration = $this->initConfiguration(true, true, true, true, true, []);
    }

    /**
     * {@inheritdoc}
     */
    protected function decorateDataItems(array $data)
    {
        return array_map(
            function ($item) {
                return new AccountDataItem($item);
            },
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function convertToArray($entity, $locale)
    {
        return [
            'number' => $entity->getNumber(),
            'name' => $entity->getName(),
            'registerNumber' => $entity->getRegisterNumber(),
            'placeOfJurisdiction' => $entity->getPlaceOfJurisdiction(),
            'uid' => $entity->getUid(),
            'corporation' => $entity->getCorporation(),
            'created' => $entity->getCreated(),
            'creator' => $entity->getCreator(),
            'changed' => $entity->getChanged(),
            'changer' => $entity->getChanger(),
            'medias' => $entity->getMedias(),
            'emails' => $this->getEmails($entity),
            'phones' => $this->getPhones($entity),
            'faxes' => $this->getFaxes($entity),
            'urls' => $this->getUrls($entity),
            'tags' => $this->getTags($entity),
            'categories' => $this->getCategories($entity, $locale),
        ];
    }
}
