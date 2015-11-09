<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Structure;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Export\ContentExportManager;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Import\ContentImportManager;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;

/**
 * extends structure with seo content.
 */
class ExcerptStructureExtension extends AbstractExtension implements ExportExtensionInterface
{
    /**
     * name of structure extension.
     */
    const EXCERPT_EXTENSION_NAME = 'excerpt';

    /**
     * will be filled with data in constructor
     * {@inheritdoc}
     */
    protected $properties = [];

    /**
     * {@inheritdoc}
     */
    protected $name = self::EXCERPT_EXTENSION_NAME;

    /**
     * {@inheritdoc}
     */
    protected $additionalPrefix = self::EXCERPT_EXTENSION_NAME;

    /**
     * @var StructureInterface
     */
    protected $excerptStructure;

    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ContentExportManager
     */
    protected $contentExportManager;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @var string
     */
    private $languageCode;

    public function __construct(
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        ContentExportManager $contentExportManager,
        ContentImportManager $contentImportManager
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->contentExportManager = $contentExportManager;
        $this->contentImportManager = $contentImportManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        foreach ($this->excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (isset($data[$property->getName()])) {
                $property->setValue($data[$property->getName()]);
                $contentType->write(
                    $node,
                    new TranslatedProperty(
                        $property,
                        $languageCode,
                        $this->languageNamespace,
                        $this->additionalPrefix
                    ),
                    null, // userid
                    $webspaceKey,
                    $languageCode,
                    null // segmentkey
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        $data = [];
        foreach ($this->excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $contentType->read(
                $node,
                new TranslatedProperty(
                    $property,
                    $languageCode,
                    $this->languageNamespace,
                    $this->additionalPrefix
                ),
                $webspaceKey,
                $languageCode,
                null // segmentkey
            );

            $data[$property->getName()] = $property->getValue();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setLanguageCode($languageCode, $languageNamespace, $namespace)
    {
        // lazy load excerpt structure to avoid redeclaration of classes
        // should be done before parent::setLanguageCode because it uses the $thi<->properties
        // which will be set in initExcerptStructure
        if ($this->excerptStructure === null) {
            $this->initProperties();
        }
        $this->languageCode = $languageCode;

        parent::setLanguageCode($languageCode, $languageNamespace, $namespace);
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData($container)
    {
        $container = new ExcerptValueContainer($container);

        $data = [];
        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            if ($container->__isset($property->getName())) {
                $property->setValue($container->__get($property->getName()));
                $contentType = $this->contentTypeManager->get($property->getContentTypeName());
                $data[$property->getName()] = $contentType->getContentData($property);
            }
        }

        return $data;
    }

    /**
     * Returns and caches excerpt-structure.
     *
     * @return StructureInterface
     */
    private function getExcerptStructure()
    {
        if ($this->excerptStructure === null) {
            $this->excerptStructure = $this->structureManager->getStructure(self::EXCERPT_EXTENSION_NAME);
            $this->excerptStructure->setLanguageCode($this->languageCode);
        }

        return $this->excerptStructure;
    }

    /**
     * initiates structure and properties.
     */
    private function initProperties()
    {
        /** @var PropertyInterface $property */
        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            $this->properties[] = $property->getName();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function export($properties, $format = null)
    {
        $container = new ExcerptValueContainer($properties);

        $data = [];
        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            if ($container->__isset($property->getName())) {
                $property->setValue($container->__get($property->getName()));
                $contentType = $this->contentTypeManager->get($property->getContentTypeName());
                if ($this->contentExportManager->hasExport($property->getContentTypeName(), $format)) {
                    $options = $this->contentExportManager->getOptions($property->getContentTypeName(), $format);

                    $data[$property->getName()] = [
                        'name' => $property->getName(),
                        'value' => $contentType->exportData($property->getValue()),
                        'type' => $property->getContentTypeName(),
                        'options' => $options,
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportPropertyNames()
    {
        $propertyNames = [];

        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            $propertyNames[] = $property->getName();
        }

        return $propertyNames;
    }

    /**
     * {@inheritdoc}
     */
    public function import(NodeInterface $node, $data, $webspaceKey, $languageCode, $format)
    {
        $this->setLanguageCode($languageCode, 'i18n', $format);

        foreach ($this->excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (
                isset($data[$property->getName()])
                && $this->contentImportManager->hasImport($property->getContentTypeName(), $format)
            ) {
                $property->setValue($data[$property->getName()]);
                /** @var ContentTypeExportInterface $contentType */
                $contentType->importData(
                    $node,
                    new TranslatedProperty(
                        $property,
                        $languageCode,
                        $this->languageNamespace,
                        $this->additionalPrefix
                    ),
                    null, // userid
                    $webspaceKey,
                    $languageCode,
                    null // segmentkey
                );
            }
        }
    }
}
