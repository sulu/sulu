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
     * Returns a page structure for given key
     *
     * @param $key string
     * @return StructureInterface
     */
    public function getPage($key);

    /**
     * @return StructureInterface[]
     */
    public function getPages();

    /**
     * add dynamically an extension to structures
     * @param StructureExtensionInterface $extension
     * @param string $template default is all templates
     */
    public function addExtension(StructureExtensionInterface $extension, $template = 'all');

    /**
     * Returns extensions for structure
     * @param string $key
     * @return StructureExtensionInterface[]
     */
    public function getExtensions($key);

    /**
     * Indicates that the structure has a extension
     * @param string $key
     * @param string $name
     * @return boolean
     */
    public function hasExtension($key, $name);

    /**
     * Returns a extension
     * @param string $key
     * @param string $name
     * @return StructureExtensionInterface
     */
    public function getExtension($key, $name);
}
