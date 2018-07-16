<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Metadata;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

/**
 * This class fully implements the MetadataFactoryInterface by composing
 * the "base" metadata factory and the node mxins.
 */
class MetadataFactory implements MetadataFactoryInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForAlias($alias)
    {
        return $this->metadataFactory->getMetadataForAlias($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForPhpcrType($phpcrType)
    {
        return $this->metadataFactory->getMetadataForPhpcrType($phpcrType);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataForPhpcrType($phpcrType)
    {
        return $this->metadataFactory->hasMetadataForPhpcrType($phpcrType);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForClass($class)
    {
        return $this->metadataFactory->getMetadataForClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataForClass($class)
    {
        return $this->metadataFactory->hasMetadataForClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAlias($alias)
    {
        return $this->metadataFactory->hasAlias($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return $this->metadataFactory->getAliases();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForPhpcrNode(NodeInterface $node)
    {
        if (false === $node->hasProperty('jcr:mixinTypes')) {
            return $this->getUnknownMetadata();
        }

        $mixinTypes = (array) $node->getPropertyValue('jcr:mixinTypes');

        foreach ($mixinTypes as $mixinType) {
            if (true == $this->metadataFactory->hasMetadataForPhpcrType($mixinType)) {
                return $this->metadataFactory->getMetadataForPhpcrType($mixinType);
            }
        }

        return $this->getUnknownMetadata();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata()
    {
        return $this->metadataFactory->getAllMetadata();
    }

    /**
     * @return Metadata
     */
    private function getUnknownMetadata()
    {
        $metadata = new Metadata();
        $metadata->setAlias(null);
        $metadata->setPhpcrType(null);
        $metadata->setClass(UnknownDocument::class);

        return $metadata;
    }
}
