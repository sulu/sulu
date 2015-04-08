<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace DTL\Component\Content\Structure\Factory;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\FileLocator;
use Doctrine\Common\Inflector\Inflector;

/**
 * Create new (mapped) structures using the provided loader.
 */
class StructureFactory implements StructureFactoryInterface
{
    /**
     * @var array
     */
    private $typePaths = array();

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var boolean
     */
    private $debug;

    /**
     * @param LoaderInterface $loader
     * @param array $typePaths
     * @param mixed $cachePath
     * @param mixed $debug
     */
    public function __construct(LoaderInterface $loader, array $typePaths, $cachePath, $debug = false)
    {
        $this->typePaths = $typePaths;
        $this->cachePath = $cachePath;
        $this->loader = $loader;
        $this->debug = $debug;
    }

    /**
     * {@inheritDoc}
     */
    public function getStructure($type, $structureType, $asModel = false)
    {
        if (!isset($this->typePaths[$type])) {
            throw new Exception\DocumentTypeNotFoundException(sprintf(
                'Structure path for document type "%s" is not mapped. Mapped structure types: "%s"',
                $type,
                implode('", "', array_keys($this->typePaths))
            ));
        }

        if (!is_string($structureType)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected string for structureType, got: %s',
                is_object($structureType) ? get_class($structureType) : gettype($structureType)
            ));
        }

        $cachePath = sprintf(
            '%s/%s%s', 
            $this->cachePath,
            Inflector::camelize($type),
            Inflector::camelize($structureType)
        );

        $cache = new ConfigCache($cachePath, $this->debug);

        if ($this->debug || !$cache->isFresh()) {
            $fileLocator = new FileLocator($this->typePaths[$type]);

            try {
                $filePath = $fileLocator->locate(sprintf('%s.xml', $structureType));
            } catch (\InvalidArgumentException $e) {
                throw new Exception\StructureTypeNotFoundException(sprintf(
                    'Could not load structure type "%s" for document type "%s", looked in "%s"',
                    $structureType,
                    $type,
                    implode('", "', $this->typePaths[$type])
                ));
            }

            $metadata =  $this->loader->load($filePath);
            $resources = array(new FileResource($filePath));

            $cache->write(
                sprintf('<?php $metadata = \'%s\';', serialize($metadata)),
                $resources
            );
        }

        require($cachePath);

        $structure = unserialize($metadata);

        if ($asModel) {
            return $structure->transformToModel();
        }

        return $structure;
    }

    /**
     * {@inheritDoc}
     */
    public function getStructures($type)
    {
        $structureNames = $this->getStructureNames($type);
        $structures = array();

        foreach ($structureNames as $structureName) {
            $structures[] = $this->getStructure($type, $structureName);
        }

        return $structures;
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
        $structureNames = array();
        foreach ($this->typePaths[$type] as $structurePath) {
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
}
