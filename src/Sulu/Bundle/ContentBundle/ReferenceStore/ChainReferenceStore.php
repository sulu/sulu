<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\ReferenceStore;

use Ramsey\Uuid\Uuid;

/**
 * Combines other reference-stores.
 */
class ChainReferenceStore implements ReferenceStoreInterface
{
    const DELIMITER = '-';

    /**
     * @var ReferenceStoreInterface[]
     */
    private $stores = [];

    /**
     * @param ReferenceStoreInterface[] $stores
     */
    public function __construct(array $stores)
    {
        $this->stores = $stores;
    }

    /**
     * {@inheritdoc}
     */
    public function add($id)
    {
        $position = strpos($id, self::DELIMITER);
        if (false == $position) {
            throw new ReferenceStoreInvalidIdException($id);
        }

        $alias = substr($id, 0, $position);
        $id = substr($id, $position + strlen(self::DELIMITER));

        if (!array_key_exists($alias, $this->stores)) {
            throw new ReferenceStoreNotExistsException($alias, array_keys($this->stores));
        }

        $this->stores[$alias]->add($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        $ids = [];
        foreach ($this->stores as $alias => $store) {
            $ids = array_merge(
                $ids,
                array_map(
                    function ($item) use ($alias) {
                        if (Uuid::isValid($item)) {
                            return $item;
                        }

                        return $alias . '-' . $item;
                    },
                    $store->getAll()
                )
            );
        }

        return $ids;
    }
}
