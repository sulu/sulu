<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Factory;

use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Create new (mapped) structures using the provided loader.
 */
class StructureMetadataFactory implements StructureMetadataFactoryInterface
{
    /**
     * @var array
     */
    private $typePaths = [];

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var array
     */
    private $defaultTypes;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param LoaderInterface $loader
     * @param array           $typePaths
     * @param array           $defaultTypes
     * @param mixed           $cachePath
     * @param mixed           $debug
     */
    public function __construct(
        LoaderInterface $loader,
        array $typePaths,
        array $defaultTypes,
        $cachePath,
        $debug = false
    ) {
        $this->typePaths = $typePaths;
        $this->cachePath = $cachePath;
        $this->loader = $loader;
        $this->debug = $debug;
        $this->defaultTypes = $defaultTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructureMetadata($type, $structureType = null)
    {
        $cacheKey = $type . $structureType;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $this->assertExists($type);

        if (!$structureType) {
            $structureType = $this->getDefaultStructureType($type);
        }

        if (!is_string($structureType)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected string for structureType, got: %s',
                    is_object($structureType) ? get_class($structureType) : gettype($structureType)
                )
            );
        }

        $cachePath = sprintf(
            '%s%s%s%s',
            $this->cachePath,
            DIRECTORY_SEPARATOR,
            Inflector::camelize($type),
            Inflector::camelize($structureType)
        );

        $cache = new ConfigCache($cachePath, $this->debug);

        if ($this->debug || !$cache->isFresh()) {
            $paths = $this->getPaths($type);

            // reverse paths, so that the last path overrides previous ones
            $fileLocator = new FileLocator(array_reverse($paths));

            try {
                $filePath = $fileLocator->locate(sprintf('%s.xml', $structureType));
            } catch (\InvalidArgumentException $e) {
                throw new Exception\StructureTypeNotFoundException(
                    sprintf(
                        'Could not load structure type "%s" for document type "%s", looked in "%s"',
                        $structureType,
                        $type,
                        implode('", "', $paths)
                    ), null, $e
                );
            }

            $metadata = $this->loader->load($filePath, $type);

            $resources = [new FileResource($filePath)];

            $cache->write(
                sprintf('<?php $metadata = \'%s\';', serialize($metadata)),
                $resources
            );
        }

        require $cachePath;

        $structure = unserialize($metadata);

        $this->cache[$cacheKey] = $structure;

        return $structure;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructures($type)
    {
        $structureNames = $this->getStructureNames($type);
        $structures = [];

        foreach ($structureNames as $structureName) {
            $structures[] = $this->getStructureMetadata($type, $structureName);
        }

        return $structures;
    }

    /**
     * {@inheritdoc}
     */
    public function hasStructuresFor($type)
    {
        return isset($this->typePaths[$type]);
    }

    /**
     * Return the structure names for the given type
     * (not necessarily valid).
     *
     * @param string $type
     *
     * @return string[]
     */
    private function getStructureNames($type)
    {
        $this->assertExists($type);
        $structureNames = [];

        foreach ($this->typePaths[$type] as $pathConfig) {
            $structurePath = $pathConfig['path'];

            // Ignore not-existing paths
            if (!file_exists($structurePath)) {
                continue;
            }

            $iterator = new \DirectoryIterator($structurePath);
            foreach ($iterator as $file) {
                $ext = $file->getExtension();

                if ($ext !== 'xml') {
                    continue;
                }

                $structureNames[] = $file->getBasename('.' . $ext);
            }
        }

        return $structureNames;
    }

    /**
     * Assert type exists.
     *
     * @param string $type
     */
    private function assertExists($type)
    {
        if (!isset($this->typePaths[$type])) {
            throw new Exception\DocumentTypeNotFoundException(
                sprintf(
                    'Structure path for document type "%s" is not mapped. Mapped structure types: "%s"',
                    $type,
                    implode('", "', array_keys($this->typePaths))
                )
            );
        }
    }

    /**
     * Get the paths from the type path configuration.
     *
     * @param string $type
     *
     * @return array
     */
    private function getPaths($type)
    {
        $typeConfigs = $this->typePaths[$type];
        $paths = [];

        foreach ($typeConfigs as $typeConfig) {
            $paths[] = $typeConfig['path'];
        }

        return $paths;
    }

    /**
     * Return the default structure type for the the given document type.
     *
     * @param string $type
     *
     * @return string
     */
    private function getDefaultStructureType($type)
    {
        if (!isset($this->defaultTypes[$type])) {
            throw new \RuntimeException(
                sprintf(
                    'No structure type was available and no default exists for document with alias "%s"',
                    $type
                )
            );
        }

        return $this->defaultTypes[$type];
    }
}
