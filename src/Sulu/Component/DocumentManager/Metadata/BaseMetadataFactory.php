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
use Sulu\Component\DocumentManager\ClassNameInflector;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\MetadataNotFoundException;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Simple metadata factory which uses an array map.
 *
 * Note that this class does not  implement the getMetadataForPhpcrNode method
 * as that would require a circular dependency.
 */
class BaseMetadataFactory implements MetadataFactoryInterface
{
    /**
     * @var array
     */
    private $aliasMap = [];

    /**
     * @var array
     */
    private $classMap = [];

    /**
     * @var array
     */
    private $phpcrTypeMap = [];

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var Metadata[]
     */
    private $metadata = [];

    /**
     * @param array $mapping
     */
    public function __construct(EventDispatcherInterface $dispatcher, array $mapping)
    {
        $this->dispatcher = $dispatcher;

        foreach ($mapping as $map) {
            $this->aliasMap[$map['alias']] = $map;
            $this->classMap[$map['class']] = $map;
            $this->phpcrTypeMap[$map['phpcr_type']] = $map;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForAlias($alias)
    {
        if (!isset($this->aliasMap[$alias])) {
            throw new MetadataNotFoundException(sprintf(
                'Metadata with alias "%s" not found, known aliases: "%s"',
                $alias, implode('", "', array_keys($this->aliasMap))
            ));
        }

        $map = $this->aliasMap[$alias];

        return $this->loadMetadata($map);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForPhpcrType($phpcrType)
    {
        if (!isset($this->phpcrTypeMap[$phpcrType])) {
            throw new MetadataNotFoundException(sprintf(
                'Metadata with phpcrType "%s" not found, known phpcrTypes: "%s"',
                $phpcrType, implode('", "', array_keys($this->phpcrTypeMap))
            ));
        }

        $map = $this->phpcrTypeMap[$phpcrType];

        return $this->loadMetadata($map);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataForPhpcrType($phpcrType)
    {
        return isset($this->phpcrTypeMap[$phpcrType]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataForClass($class)
    {
        $class = ClassNameInflector::getUserClassName($class);

        return isset($this->classMap[$class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForClass($class)
    {
        $class = ClassNameInflector::getUserClassName($class);

        if (!isset($this->classMap[$class])) {
            throw new MetadataNotFoundException(sprintf(
                'Metadata with class "%s" not found, known classes: "%s"',
                $class, implode('", "', array_keys($this->classMap))
            ));
        }

        $map = $this->classMap[$class];

        return $this->loadMetadata($map);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAlias($alias)
    {
        return isset($this->aliasMap[$alias]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return array_keys($this->aliasMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForPhpcrNode(NodeInterface $node)
    {
        throw new \BadMethodCallException(
            'The BaseMetadataFactory does not implement this method'
        );
    }

    /**
     * @return array
     */
    public function getPhpcrTypeMap()
    {
        return $this->phpcrTypeMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata()
    {
        $metadatas = [];
        foreach (array_keys($this->aliasMap) as $alias) {
            $metadatas[] = $this->getMetadataForAlias($alias);
        }

        return $metadatas;
    }

    /**
     * @param array $mapping
     *
     * @return Metadata
     */
    private function loadMetadata($mapping)
    {
        $mapping = array_merge([
            'alias' => null,
            'phpcr_type' => null,
            'form_type' => null,
            'class' => null,
            'mapping' => [],
            'sync_remove_live' => true,
            'set_default_author' => true,
        ], $mapping);

        if (isset($this->metadata[$mapping['alias']])) {
            return $this->metadata[$mapping['alias']];
        }

        $metadata = new Metadata();
        $metadata->setAlias($mapping['alias']);
        $metadata->setPhpcrType($mapping['phpcr_type']);
        $metadata->setFormType($mapping['form_type']);
        $metadata->setClass($mapping['class']);
        $metadata->setSyncRemoveLive($mapping['sync_remove_live']);
        $metadata->setDefaultAuthor($mapping['set_default_author']);

        foreach ($mapping['mapping'] as $fieldName => $fieldMapping) {
            $fieldMapping = array_merge([
                'encoding' => 'content',
                'property' => $fieldName,
            ], $fieldMapping);
            $metadata->addFieldMapping($fieldName, $fieldMapping);
        }

        $event = new MetadataLoadEvent($metadata);
        $this->dispatcher->dispatch(Events::METADATA_LOAD, $event);

        $this->metadata[$mapping['alias']] = $metadata;

        return $metadata;
    }
}
