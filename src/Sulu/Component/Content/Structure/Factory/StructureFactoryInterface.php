<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Structure\Factory;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\FileLocator;
use Doctrine\Common\Inflector\Inflector;

interface StructureFactoryInterface
{
    /**
     * Return the structure of the given $type and $structureType
     *
     * @param mixed $type The primary system type, e.g. page, snippet
     * @param mixed $structureType The secondary user type
     *
     * @throws Exception\StructureTypeNotFoundException If the structure was not found
     * @throws Exception\DocumentTypeNotFoundException If the document type was not mapped
     *
     * @return StructureInterface
     */
    public function getStructure($type, $structureType);

    /**
     * Return all structures of the given type
     *
     * @param string
     *
     * @return Structure[]
     */
    public function getStructures($type);
}
