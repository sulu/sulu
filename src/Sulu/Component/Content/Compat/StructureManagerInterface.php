<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface of StructureManager.
 */
interface StructureManagerInterface extends ContainerAwareInterface
{
    /**
     * Returns structure for given key and type.
     *
     * @param string $key
     * @param string $type
     *
     * @return StructureInterface
     */
    public function getStructure($key, $type = Structure::TYPE_PAGE);

    /**
     * Return all the structures of the given type.
     *
     * @param string $type
     *
     * @return StructureInterface[]
     */
    public function getStructures($type = Structure::TYPE_PAGE);

    /**
     * Wrap the given Structure with a legacy (bridge) structure.
     *
     * @param string $type
     * @param StructureMetadata $structure
     *
     * @return StructureInterface
     */
    public function wrapStructure($type, StructureMetadata $structure);
}
