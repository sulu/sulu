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
use Sulu\Component\Content\Structure\Structure as NewStructure;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;

/**
 * generates subclasses of structure to match template definitions.
 * this classes will be cached in Symfony cache
 */
class StructureManager extends ContainerAware implements StructureManagerInterface
{
    private $structureFactory;
    private $extensionManager;
    private $inspector;
    private $propertyFactory;

    private $typeMap = array(
        'page' => '\Sulu\Component\Content\Compat\Structure\PageBridge',
        'snippet' => '\Sulu\Component\Content\Compat\Structure\SnippetBridge',
    );

    /**
     * @param StructureFactory  $structureFactory
     * @param ExtensionManager  $extensionManager
     * @param DocumentInspector $inspector
     */
    public function __construct(
        StructureFactory $structureFactory,
        ExtensionManager $extensionManager,
        DocumentInspector $inspector,
        LegacyPropertyFactory $propertyFactory
    ) {
        $this->structureFactory = $structureFactory;
        $this->extensionManager = $extensionManager;
        $this->inspector = $inspector;
        $this->propertyFactory = $propertyFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getStructure($key, $type = Structure::TYPE_PAGE)
    {
        return $this->wrapStructure($type, $this->structureFactory->getStructure($type, $key));
    }

    /**
     * {@inheritDoc}
     */
    public function getStructures($type = Structure::TYPE_PAGE)
    {
        $wrappedStructures = array();
        $structures = $this->structureFactory->getStructures($type);

        foreach ($structures as $structure) {
            $wrappedStructures[] = $this->wrapStructure($type, $structure);
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
        return $this->extensionManager->getExtensions($key);
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtension($key, $name)
    {
        return $this->extensionManager->hasExtension($key, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension($key, $name)
    {
        return $this->extensionManager->getExtension($key, $name);
    }

    /**
     * Wrap the given Structure with a legacy (bridge) structure
     *
     * @param Structure
     *
     * @return StructureBridge
     */
    public function wrapStructure($type, NewStructure $structure)
    {
        if (!isset($this->typeMap[$type])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid legacy type "%s", known types: "%s"',
                $type, implode('", "', array_keys($this->typeMap))
            ));
        }

        $class = $this->typeMap[$type];

        return new $class($structure, $this->inspector, $this->propertyFactory);
    }
}
