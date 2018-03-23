<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata\Type;

use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;

interface TypesInterface extends ResourceMetadataInterface
{
    /**
     * @return Type[]
     */
    public function getTypes(): array;

    public function addType(Type $type): void;
}
