<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

/**
 * Interface for the manager of contact and account combination.
 */
interface CustomerManagerInterface
{
    /**
     * Returns accounts and contact in a single array.
     *
     * Example: $ids = ['c5','a2','c2']
     * Returns: [
     *              ['id' => 'c5', 'name' => 'Max Mustermann'],
     *              ['id' => 'a2', 'name' => 'MASSIVE ART WebServices GmbH'],
     *              ['id' => 'c2', 'name' => 'Erika Mustermann']
     *          ]
     *
     * @param array $ids
     *
     * @return array
     */
    public function findByIds($ids);
}
