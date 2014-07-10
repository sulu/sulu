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

use Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;

/**
 * Defines some common REST functionalities
 */
class RestHelper implements RestHelperInterface
{
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

    /**
     * {@inheritDoc}
     */
    public function processSubEntities($entities, $requestEntities, $deleteCallback, $updateCallback, $addCallback)
    {
        $success = true;

        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $this->findMatch($requestEntities, $entity->getId(), $matchedEntry, $matchedKey);

                if ($matchedEntry == null) {
                    // delete entity if it is not listed anymore
                    $deleteCallback($entity);
                } else {
                    // update entity if it is matched
                    $success = $updateCallback($entity, $matchedEntry);
                    if (!$success) {
                        break;
                    }
                }

                // Remove done element from array
                if ($matchedKey != null) {
                    unset($requestEntities[$matchedKey]);
                }
            }
        }

        // The entity which have not been delete or updated have to be added
        if (!empty($requestEntities)) {
            foreach ($requestEntities as $entity) {
                if (!$success) {
                    break;
                }
                $success = $addCallback($entity);
            }
        }

        // FIXME: this is just a hack to avoid relations that start with index != 0
        // FIXME: otherwise deserialization process will parse relations as object instead of an array
        // reindex entities
        if (sizeof($entities) > 0 && method_exists($entities, 'getValues')) {
            $newEntities = $entities->getValues();
            $entities->clear();
            foreach ($newEntities as $value) {
                $entities->add($value);
            }
        }

        return $success;
    }

    /**
     * Tries to find an given id in a given set of entities. Returns the entity itself and its key with the
     * $matchedEntry and $matchKey parameters.
     * @param array $requestEntities The set of entities to search in
     * @param integer $id The id to search
     * @param array $matchedEntry
     * @param string $matchedKey
     */
    protected function findMatch($requestEntities, $id, &$matchedEntry, &$matchedKey)
    {
        $matchedEntry = null;
        $matchedKey = null;
        if (!empty($requestEntities)) {
            foreach ($requestEntities as $key => $entity) {
                if (isset($entity['id']) && $entity['id'] == $id) {
                    $matchedEntry = $entity;
                    $matchedKey = $key;
                }
            }
        }
    }
} 
