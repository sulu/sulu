<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Exception\MetadataNotFoundException;

/**
 * Simple metadata factory which uses an array
 */
class MetadataFactory
{
    private $aliasMap = array();
    private $classMap = array();
    private $phpcrTypeMap = array();

    public function __construct(array $mapping)
    {
        foreach ($mapping as $map) {
            $this->aliasMap[$map['alias']] = $map;
            $this->classMap[$map['class']] = $map;
            $this->phpcrTypeMap[$map['phpcr_type']] = $map;
        }
    }

    public function getMetadataForAlias($alias)
    {
        if (!isset($this->aliasMap[$alias])) {
            throw new MetadataNotFoundException(sprintf(
                'Metadata with alias "%s" not found, known aliases: "%s"',
                $alias, implode('", "', array_keys($this->aliasMap))
            ));
        }

        $map = $this->aliasMap[$alias];
        return $this->getMetadata($map);
    }

    public function getMetadataForPhpcrType($phpcrType)
    {
        if (!isset($this->phpcrTypeMap[$phpcrType])) {
            throw new MetadataNotFoundException(sprintf(
                'Metadata with phpcrType "%s" not found, known phpcrTypees: "%s"',
                $phpcrType, implode('", "', array_keys($this->phpcrTypeMap))
            ));
        }

        $map = $this->phpcrTypeMap[$phpcrType];
        return $this->getMetadata($map);
    }

    public function hasMetadataForPhpcrType($phpcrType)
    {
        return isset($this->phpcrTypeMap[$phpcrType]);
    }

    public function getMetadataForClass($class)
    {
        if (!isset($this->classMap[$class])) {
            throw new MetadataNotFoundException(sprintf(
                'Metadata with class "%s" not found, known classes: "%s"',
                $class, implode('", "', array_keys($this->classMap))
            ));
        }

        $map = $this->classMap[$class];
        return $this->getMetadata($map);
    }

    public function hasAlias($alias)
    {
        return isset($this->aliasMap[$alias]);
    }

    private function getMetadata($mapping)
    {
        $metadata = new Metadata();
        $metadata->setAlias($mapping['alias']);
        $metadata->setPhpcrType($mapping['phpcr_type']);
        $metadata->setClass($mapping['class']);

        return $metadata;
    }
}
