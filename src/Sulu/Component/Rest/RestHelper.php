<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Sulu\Component\Persistence\RelationTrait;
use Sulu\Component\Rest\Exception\SearchFieldNotFoundException;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;

/**
 * Defines some common REST functionalities.
 */
class RestHelper implements RestHelperInterface
{
    use RelationTrait;

    public function __construct(private ListRestHelper $listRestHelper)
    {
    }

    public function initializeListBuilder(ListBuilderInterface $listBuilder, array $fieldDescriptors)
    {
        $listBuilder->limit($this->listRestHelper->getLimit());
        $listBuilder->setCurrentPage($this->listRestHelper->getPage());
        $listBuilder->setFieldDescriptors($fieldDescriptors);
        $listBuilder->setIds($this->listRestHelper->getIds());
        $listBuilder->setExcludedIds($this->listRestHelper->getExcludedIds());
        $listBuilder->filter($this->listRestHelper->getFilter());

        $fields = $this->listRestHelper->getFields();
        if (null != $fields) {
            foreach ($fields as $field) {
                if (!\array_key_exists($field, $fieldDescriptors)) {
                    continue;
                }

                $listBuilder->addSelectField($fieldDescriptors[$field]);
            }
        } else {
            $listBuilder->setSelectFields($fieldDescriptors);
        }

        $search = $this->listRestHelper->getSearchPattern();
        if ($search) {
            $searchFields = $this->listRestHelper->getSearchFields();

            if (!$searchFields) {
                $searchFields = $this->getDefaultSearchFields($fieldDescriptors);
            }

            foreach ($searchFields as $searchField) {
                if (!isset($fieldDescriptors[$searchField])) {
                    throw new SearchFieldNotFoundException($searchField);
                }

                $fieldDescriptor = $fieldDescriptors[$searchField];

                if (FieldDescriptorInterface::SEARCHABILITY_NEVER === $fieldDescriptor->getSearchability()) {
                    continue;
                }

                $listBuilder->addSearchField($fieldDescriptor);
            }

            $listBuilder->search($this->listRestHelper->getSearchPattern());
        }

        $sortBy = $this->listRestHelper->getSortColumn();
        if (null != $sortBy && \array_key_exists($sortBy, $fieldDescriptors)) {
            $listBuilder->sort($fieldDescriptors[$sortBy], $this->listRestHelper->getSortOrder());
        }
    }

    /**
     * @param FieldDescriptorInterface[] $fieldDescriptors
     *
     * @return string[]
     */
    private function getDefaultSearchFields(array $fieldDescriptors): array
    {
        $defaultSearchFields = [];
        foreach ($fieldDescriptors as $fieldDescriptor) {
            if (FieldDescriptorInterface::SEARCHABILITY_YES === $fieldDescriptor->getSearchability()) {
                $defaultSearchFields[] = $fieldDescriptor->getName();
            }
        }

        return $defaultSearchFields;
    }
}
