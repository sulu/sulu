<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Search;

use Massive\Bundle\SearchBundle\Search\QueryHit;
use Massive\Bundle\SearchBundle\Search\SearchResult;
use Sulu\Component\Rest\ListBuilder\Search\FieldDescriptor\SearchFieldDescriptor;

class ListResponse extends \IteratorIterator
{
    /**
     * @var SearchFieldDescriptor[]
     */
    private $fieldDescriptors;

    /**
     * @param SearchResult $searchResult
     * @param SearchFieldDescriptor[] $fieldDescriptors
     */
    public function __construct(SearchResult $searchResult, array $fieldDescriptors)
    {
        parent::__construct($searchResult);

        $this->fieldDescriptors = $fieldDescriptors;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        /** @var QueryHit $item */
        $item = parent::current();

        $result = [];
        foreach ($this->fieldDescriptors as $fieldDescriptor) {
            $result[$fieldDescriptor->getName()] = $item->getDocument()
                ->getField($fieldDescriptor->getFieldName())
                ->getValue();
        }

        return $result;
    }
}
