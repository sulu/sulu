<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Metadata\ClassMetadata;

/**
 * Each provider returns a single type of metadata for the given class. The ChainProvider merges them and returns
 * the whole metadata.
 *
 * In case of using the jms/metadata component you can use the metadata-factory adapter to provide metadata for
 * the list-builder.
 */
interface ProviderInterface
{
    /**
     * Load metadata for the given class.
     *
     * @param string $className
     *
     * @return ClassMetadata
     */
    public function getMetadataForClass($className);
}
