<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata as ContentStructureMetadata;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;

class StructureLoader
{
    /**
     * @var StructureMetadataFactory
     */
    private $structureMetadataFactory;

    /**
     * @var FormMetadataMapper
     */
    private $formMetadataMapper;

    /**
     * @var string[]
     */
    private $locales;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $debug;

    /**
     * StructureLoader constructor.
     *
     * @param $structureMetadataFactory
     * @param $formMetadataMapper
     * @param $locales
     * @param string $cacheDir
     * @param bool $debug
     */
    public function __construct(
        $structureMetadataFactory,
        $formMetadataMapper,
        $locales,
        string $cacheDir,
        bool $debug
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->formMetadataMapper = $formMetadataMapper;
        $this->locales = $locales;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function load()
    {
        $structuresMetadataByTypes = [];
        foreach ($this->structureMetadataFactory->getStructureTypes() as $structureType) {
            foreach ($this->structureMetadataFactory->getStructures($structureType) as $structureMetadata) {
                if ($structureMetadata->isInternal() || 'home' === $structureType) {
                    continue;
                }

                $structuresMetadataByTypes[$structureType][] = $structureMetadata;
            }
        }

        foreach ($this->locales as $locale) {
            foreach ($structuresMetadataByTypes as $structureType => $structuresMetadata) {
                $structure = $this->mapStructureMetadata($structuresMetadata, $locale);
                $configCache = $this->getConfigCache($structureType, $locale);
                $configCache->write(
                    serialize($structure),
                    array_map(function(ContentStructureMetadata $structureMetadata) {
                        return new FileResource($structureMetadata->getResource());
                    }, $structuresMetadata)
                );
            }
        }
    }

    /**
     * @param array $structuresMetadata
     * @param string $locale
     *
     * @return TypedFormMetadata
     *
     * @throws \Exception
     */
    public function mapStructureMetadata(array $structuresMetadata, string $locale)
    {
        $typedForm = new TypedFormMetadata();

        foreach ($structuresMetadata as $structureMetadata) {
            $form = new FormMetadata();
            $form->setName($structureMetadata->getName());
            $form->setTitle($structureMetadata->getTitle($locale) ?? ucfirst($structureMetadata->getName()));
            $this->formMetadataMapper->mapChildren($structureMetadata->getChildren(), $form, $locale);
            $form->setSchema($this->formMetadataMapper->mapSchema($structureMetadata->getProperties()));

            $typedForm->addForm($structureMetadata->getName(), $form);
        }

        return $typedForm;
    }

    private function getConfigCache(string $key, string $locale): ConfigCache
    {
        return new ConfigCache(sprintf('%s%s%s.%s', $this->cacheDir, DIRECTORY_SEPARATOR, $key, $locale), $this->debug);
    }
}
