<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use Sulu\Component\Content\StructureExtension\StructureExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface of StructureManager
 */
interface StructureManagerInterface extends ContainerAwareInterface
{
    /**
     * returns a structure for given key
     * @param $key string
     * @return mixed
     */
    public function getStructure($key);

    /**
     * @return StructureInterface[]
     */
    public function getStructures();

    /**
     * add dynamically an extension to structures
     * @param StructureExtensionInterface $extension
     * @param string $template default is all templates
     */
    public function addExtension(StructureExtensionInterface $extension, $template = 'all');
}
