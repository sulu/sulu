<?php
/*
 * This file is part of the Sulu CMS.
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
     * {@inheritDoc}
     */
    public function initializeListBuilder(ListBuilderInterface $listBuilder, array $fieldDescriptors)
    {
        $listBuilder->limit($this->listRestHelper->getLimit())->setCurrentPage($this->listRestHelper->getPage());

        $fields = $this->listRestHelper->getFields();
        if ($fields != null) {
            foreach ($fields as $field) {
                $listBuilder->addField($fieldDescriptors[$field]);
            }
        } else {
            $listBuilder->setFields($fieldDescriptors);
        }

        $searchFields = $this->listRestHelper->getSearchFields();
        if ($searchFields != null) {
            foreach ($searchFields as $searchField) {
                $listBuilder->addSearchField($fieldDescriptors[$searchField]);
            }

            $listBuilder->search($this->listRestHelper->getSearchPattern());
        }

        $sortBy = $this->listRestHelper->getSortColumn();
        if ($sortBy != null) {
            $listBuilder->sort($fieldDescriptors[$sortBy], $this->listRestHelper->getSortOrder());
        }
    }
}
