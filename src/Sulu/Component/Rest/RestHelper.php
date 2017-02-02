<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Sulu\Component\Persistence\RelationTrait;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;

/**
 * Defines some common REST functionalities.
 */
class RestHelper implements RestHelperInterface
{
    use RelationTrait;

    /**
     * @var ListRestHelperInterface
     */
    private $listRestHelper;

    public function __construct(ListRestHelper $listRestHelper)
    {
        $this->listRestHelper = $listRestHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeListBuilder(ListBuilderInterface $listBuilder, array $fieldDescriptors)
    {
        // add pagination
        $listBuilder->limit($this->listRestHelper->getLimit())->setCurrentPage($this->listRestHelper->getPage());

        // add field descriptors
        $listBuilder->setFieldDescriptors($fieldDescriptors);

        // add select fields
        $fields = $this->listRestHelper->getFields();
        if ($fields != null) {
            foreach ($fields as $field) {
                if (!array_key_exists($field, $fieldDescriptors)) {
                    continue;
                }

                $listBuilder->addSelectField($fieldDescriptors[$field]);
            }
        } else {
            $listBuilder->setSelectFields($fieldDescriptors);
        }

        // add search
        $searchFields = $this->listRestHelper->getSearchFields();
        if ($searchFields != null) {
            foreach ($searchFields as $searchField) {
                $listBuilder->addSearchField($fieldDescriptors[$searchField]);
            }

            $listBuilder->search($this->listRestHelper->getSearchPattern());
        }

        // add sort
        $sortBy = $this->listRestHelper->getSortColumn();
        if ($sortBy != null) {
            $listBuilder->sort($fieldDescriptors[$sortBy], $this->listRestHelper->getSortOrder());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFilters(ListBuilderInterface $listBuilder, array $fieldDescriptors)
    {
        foreach ($this->listRestHelper->getFilters() as $filterKey => $filterValue) {
            if (isset($fieldDescriptors[$filterKey])) {
                $listBuilder->where($fieldDescriptors[$filterKey], $filterValue);
            }
        }
    }
}
