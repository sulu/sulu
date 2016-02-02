<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search;

use Massive\Bundle\SearchBundle\Search\Factory as BaseFactory;

/**
 * Extend the MassiveSearch factory in order to
 * use a custom document type.
 */
class Factory extends BaseFactory
{
    /**
     * {@inheritdoc}
     *
     * @return Document
     */
    public function createDocument()
    {
        return new Document();
    }
}
