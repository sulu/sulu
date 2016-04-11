<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DataFixtures;

use Sulu\Component\DocumentManager\DocumentManager;

interface DocumentFixtureInterface
{
    /**
     * Load fixtures.
     *
     * Use the document manager to create and save fixtures.
     * Be sure to call DocumentManager#save() when you are done.
     *
     * @param DocumentManager
     */
    public function load(DocumentManager $documentManager);

    /**
     * Return an integer by which the order will be determined in
     * accordance with the values returned by other fixtures.
     *
     * @return int
     */
    public function getOrder();
}
