<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use Symfony\Component\DependencyInjection\ContainerAware;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use Sulu\Component\Content\Extension\ExtensionManager;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;

/**
 * generates subclasses of structure to match template definitions.
 * this classes will be cached in Symfony cache
 */
class StructureManager extends ContainerAware implements StructureManagerInterface
{
    private $structureFactory;
    private $extensionManager;
    private $documentInspector;

    /**
     * @param StructureFactory  $structureFactory
     * @param ExtensionManager  $extensionManager
     * @param DocumentInspector $documentInspector
     */
    public function __construct(
        StructureFactory $structureFactory,
        ExtensionManager $extensionManager,
        DocumentInspector $documentInspector
    ) {
        $this->structureFactory = $structureFactory;
        $this->extensionManager = $extensionManager;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritDoc}
     */
    public function getStructure($key, $type = Structure::TYPE_PAGE)
    {
        return $this->wrapStructure($this->structureFactory->getStructure($key, $type));
    }

    /**
     * {@inheritDoc}
     */
    public function getStructures($type = Structure::TYPE_PAGE)
    {
        $wrappedStructures = array();
        $structures = $this->structureFactory->getStructures($type);

        foreach ($structures as $structure) {
            $wrappedStructures[] = $this->wrapStructure($structure);
        }

        return $wrappedStructures;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(ExtensionInterface $extension, $template = 'all')
    {
        $this->extensionManager->addExtension($extension, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions($key)
    {
        return $this->extensionmanager->getExtensions($key);
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtension($key, $name)
    {
        return $this->extensionmanager->hasExtension($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension($key, $name)
    {
        return $this->extensionmanager->getExtension($key, $name);
    }

    private function wrapStructure(Structure $structure)
    {
        return new StructureBridge($structure, $this->inspector);
    }
}
