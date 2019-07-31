<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Sulu\Bundle\AdminBundle\FormMetadata\StructureLoader;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class FormMetadataProvider implements MetadataProviderInterface, CacheWarmerInterface
{
    /**
     * @var FormXmlLoader
     */
    private $formXmlLoader;

    /**
     * @var StructureLoader
     */
    private $structureLoader;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var string[]
     */
    private $formDirectories;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var FormMetadataLoaderInterface[]
     */
    private $formMetadataLoaders;

    public function __construct(
        FormXmlLoader $formXmlLoader,
        StructureLoader $structureLoader,
        ExpressionLanguage $expressionLanguage,
        array $formDirectories,
        string $cacheDir,
        bool $debug,
        iterable $formMetadataLoaders
    ) {
        $this->formXmlLoader = $formXmlLoader;
        $this->structureLoader = $structureLoader;
        $this->expressionLanguage = $expressionLanguage;
        $this->formDirectories = $formDirectories;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
        $this->formMetadataLoaders = $formMetadataLoaders;
    }

    public function getMetadata(string $key, string $locale)
    {
        $configCache = $this->getConfigCache($key, $locale);

        if (!$configCache->isFresh()) {
            $this->warmUp($this->cacheDir);
        }

        if (!file_exists($configCache->getPath())) {
            throw new MetadataNotFoundException('form', $key);
        }

        $form = unserialize(file_get_contents($configCache->getPath()));

        if ($form instanceof FormMetadata) {
            $this->evaluateFormItemExpressions($form->getItems());
        } elseif ($form instanceof TypedFormMetadata) {
            foreach ($form->getForms() as $formType) {
                $this->evaluateFormItemExpressions($formType->getItems());
            }
        }

        return $form;
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function evaluateFormItemExpressions(array $items)
    {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $this->evaluateFormItemExpressions($item->getItems());
            }

            if ($item instanceof FieldMetadata) {
                foreach ($item->getTypes() as $type) {
                    $this->evaluateFormItemExpressions($type->getItems());
                }

                foreach ($item->getOptions() as $option) {
                    if (OptionMetadata::TYPE_EXPRESSION === $option->getType()) {
                        $option->setValue($this->expressionLanguage->evaluate($option->getValue()));
                    }
                }
            }
        }
    }

    public function warmUp($cacheDir)
    {
        $formsMetadata = [];
        foreach ($this->formMetadataLoaders as $metadataLoader) {
            $formsMetadata = array_merge_recursive($metadataLoader->load(), $formsMetadata);
        }

        $this->structureLoader->load();
    }

    public function isOptional()
    {
        return false;
    }

    private function getConfigCache(string $key, string $locale): ConfigCache
    {
        return new ConfigCache(sprintf('%s%s%s.%s', $this->cacheDir, DIRECTORY_SEPARATOR, $key, $locale), $this->debug);
    }
}
