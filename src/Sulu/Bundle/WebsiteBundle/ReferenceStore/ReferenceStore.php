<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\ReferenceStore;

/**
 * Represents implementation for reference-store.
 */
class ReferenceStore implements ReferenceStoreInterface
{
    /**
     * @var array
     */
    private $ids = [];

    /**
     * {@inheritdoc}
     */
    public function add($id)
    {
        if (in_array($id, $this->ids)) {
            return;
        }

        $this->ids[] = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        return $this->ids;
    }
}
