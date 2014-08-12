<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Structure;

use PHPCR\NodeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureExtension\StructureExtension;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;

/**
 * extends structure with seo content
 * @package Sulu\Bundle\ContentBundle\Content\Structure
 */
class ExcerptStructureExtension extends StructureExtension
{
    /**
     * name of structure extension
     */
    const EXCERPT_EXTENSION_NAME = 'excerpt';

    /**
     * will be filled with data in constructor
     * {@inheritdoc}
     */
    protected $properties = array();

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
     * @var string
     */
    private $languageNamespace;

    function __construct(StructureManagerInterface $structureManager, ContentTypeManagerInterface $contentTypeManager)
    {
        $this->excerptStructure = $structureManager->getStructure(self::EXCERPT_EXTENSION_NAME);
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;

        /** @var PropertyInterface $property */
        foreach ($this->excerptStructure->getProperties() as $property) {
            $this->properties[] = $property->getName();
        }
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
                        $languageCode . '-' . $this->additionalPrefix,
                        $this->languageNamespace
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
        $data = array();
        foreach ($this->excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $contentType->read(
                $node,
                new TranslatedProperty($property, $languageCode . '-' . $this->additionalPrefix, $this->languageNamespace),
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
        parent::setLanguageCode($languageCode, $languageNamespace, $namespace);
        $this->languageNamespace = $languageNamespace;
    }
}
