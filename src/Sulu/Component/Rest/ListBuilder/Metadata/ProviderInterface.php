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
 * Interface for metadata-providers.
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
