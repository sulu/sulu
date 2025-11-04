<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

/**
 * Defines the interface for the Factory of the DoctrineListBuilder.
 */
interface DoctrineListBuilderFactoryInterface
{
    /**
     * Creates a new DoctrineListBuilder for the given entity name and returns it.
     *
     * @param class-string $entityName
     *
     * @return DoctrineListBuilder
     */
    public function create($entityName);
}
