<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Block;

use PHPCR\NodeInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Exception\UnexpectedPropertyType;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;

/**
 * content type for block
 */
class BlockContentType extends ComplexContentType
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * template for form generation
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $languageNamespace;

    function __construct(ContentTypeManagerInterface $contentTypeManager, $template, $languageNamespace)
    {
        $this->contentTypeManager = $contentTypeManager;
        $this->template = $template;
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * {@inheritDoc}
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($property->getIsBlock()) {
            /** @var BlockPropertyInterface $blockProperty */
            $blockProperty = $property;
            while (!($blockProperty instanceof BlockPropertyInterface)) {
                $blockProperty = $blockProperty->getProperty();
            }

            // init properties
            $typeProperty = new Property('type', '', 'text_line');
            $lengthProperty = new Property('length', '', 'text_line');

            // load length
            $contentType = $this->contentTypeManager->get($lengthProperty->getContentTypeName());
            $contentType->read(
                $node,
                new BlockPropertyWrapper($lengthProperty, $property),
                $webspaceKey,
                $languageCode,
                $segmentKey
            );
            $len = $lengthProperty->getValue();

            for ($i = 0; $i < $len; $i++) {
                // load type
                $contentType = $this->contentTypeManager->get($typeProperty->getContentTypeName());
                $contentType->read(
                    $node,
                    new BlockPropertyWrapper($typeProperty, $property, $i),
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                );

                /** @var PropertyInterface $subProperty */
                foreach ($blockProperty->initProperties($i, $typeProperty->getValue()) as $key => $subProperty) {
                    if ($key !== 'type') {
                        $contentType = $this->contentTypeManager->get($subProperty->getContentTypeName());
                        $contentType->read(
                            $node,
                            new BlockPropertyWrapper($subProperty, $property, $i),
                            $webspaceKey,
                            $languageCode,
                            $segmentKey
                        );
                    }
                }
            }
        } else {
            throw new UnexpectedPropertyType($property, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasValue(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($property->getIsBlock()) {
            /** @var BlockPropertyInterface $blockProperty */
            $blockProperty = $property;
            while (!($blockProperty instanceof BlockPropertyInterface)) {
                $blockProperty = $blockProperty->getProperty();
            }

            // init properties
            $lengthProperty = new Property('length', '', 'text_line');
            $lengthBlockProperty = new BlockPropertyWrapper($lengthProperty, $property);
            $contentType = $this->contentTypeManager->get($lengthProperty->getContentTypeName());

            return $contentType->hasValue($node, $lengthBlockProperty, $webspaceKey, $languageCode, $segmentKey);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function readForPreview(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($property->getIsBlock()) {
            /** @var BlockPropertyInterface $blockProperty */
            $blockProperty = $property;
            while (!($blockProperty instanceof BlockPropertyInterface)) {
                $blockProperty = $blockProperty->getProperty();
            }

            $len = sizeof($data);

            for ($i = 0; $i < $len; $i++) {
                /** @var PropertyInterface $subProperty */
                foreach ($blockProperty->initProperties($i, $data[$i]['type']) as $key => $subProperty) {
                    if ($key !== 'type' && isset($data[$i][$subProperty->getName()])) {
                        $contentType = $this->contentTypeManager->get($subProperty->getContentTypeName());
                        $contentType->readForPreview(
                            $data[$i][$subProperty->getName()],
                            $subProperty,
                            $webspaceKey,
                            $languageCode,
                            $segmentKey
                        );
                    }
                }
            }
        } else {
            throw new UnexpectedPropertyType($property, $this);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($property->getIsBlock()) {
            /** @var BlockPropertyInterface $blockProperty */
            $blockProperty = $property;
            while (!($blockProperty instanceof BlockPropertyInterface)) {
                $blockProperty = $blockProperty->getProperty();
            }

            $data = $blockProperty->getValue();
            if (!$blockProperty->getIsMultiple()) {
                $data = array($data);
            }

            $data = array_filter($data);

            $len = count($data);

            // init properties
            $typeProperty = new Property('type', '', 'text_line');
            $lengthProperty = new Property('length', '', 'text_line');

            //save length
            $lengthProperty->setValue($len);
            $contentType = $this->contentTypeManager->get($lengthProperty->getContentTypeName());
            $contentType->write(
                $node,
                new BlockPropertyWrapper($lengthProperty, $property),
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            );

            for ($i = 0; $i < $len; $i++) {
                /** @var PropertyInterface $subProperty */
                foreach ($blockProperty->getProperties($i) as $key => $subProperty) {
                    if ($key !== 'type') {
                        // save sub property
                        $contentType = $this->contentTypeManager->get($subProperty->getContentTypeName());
                        $contentType->write(
                            $node,
                            new BlockPropertyWrapper($subProperty, $property, $i),
                            $userId,
                            $webspaceKey,
                            $languageCode,
                            $segmentKey
                        );
                    } else {
                        // save type property
                        $typeProperty->setValue($subProperty);
                        $contentType = $this->contentTypeManager->get($typeProperty->getContentTypeName());
                        $contentType->write(
                            $node,
                            new BlockPropertyWrapper($typeProperty, $property, $i),
                            $userId,
                            $webspaceKey,
                            $languageCode,
                            $segmentKey
                        );
                    }
                }
            }
        } else {
            throw new UnexpectedPropertyType($property, $this);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        // TODO: Implement remove() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritDoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return $this->prepareData(
            $property,
            function (ContentTypeInterface $contentType, $property) {
                return $contentType->getViewData($property);
            },
            false
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        return $this->prepareData(
            $property,
            function (ContentTypeInterface $contentType, $property) {
                return $contentType->getContentData($property);
            }
        );
    }

    /**
     * @param PropertyInterface $property
     * @param callable $dataCallback
     * @param bool $returnType
     * @return array
     */
    private function prepareData(PropertyInterface $property, callable $dataCallback, $returnType = true)
    {
        /** @var BlockPropertyInterface $blockProperty */
        $blockProperty = $property;
        while (!($blockProperty instanceof BlockPropertyInterface)) {
            $blockProperty = $blockProperty->getProperty();
        }

        $data = array();
        for ($i = 0; $i < $blockProperty->getLength(); $i++) {
            $properties = $blockProperty->getProperties($i);

            if ($returnType) {
                $type = $properties['type'];
                $data[$i] = array('type' => $type);
            }

            unset($properties['type']);

            foreach ($properties as $prop) {
                $contentType = $this->contentTypeManager->get($prop->getContentTypeName());
                $data[$i][$prop->getName()] = $dataCallback($contentType, $prop);
            }
        }

        if (!$property->getIsMultiple() && count($data) > 0) {
            $data = $data[0];
        }

        return $data;
    }
}
