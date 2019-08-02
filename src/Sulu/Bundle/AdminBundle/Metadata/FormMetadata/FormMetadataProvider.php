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
     * @var FormMetadataLoaderInterface[]
     */
    private $formMetadataLoaders;

    public function __construct(
        FormXmlLoader $formXmlLoader,
        StructureLoader $structureLoader,
        ExpressionLanguage $expressionLanguage,
        array $formDirectories,
        iterable $formMetadataLoaders
    ) {
        $this->formXmlLoader = $formXmlLoader;
        $this->structureLoader = $structureLoader;
        $this->expressionLanguage = $expressionLanguage;
        $this->formDirectories = $formDirectories;
        $this->formMetadataLoaders = $formMetadataLoaders;
    }

    public function getMetadata(string $key, string $locale)
    {
        $form = null;
        foreach ($this->formMetadataLoaders as $metadataLoader) {
            $form = $metadataLoader->getMetadata($key, $locale);
            if ($form) {
                break;
            }
        }
        if (!$form) {
            throw new MetadataNotFoundException('form', $key);
        }

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
        /*
        foreach ($this->formMetadataLoaders as $metadataLoader) {
            $metadataLoader->load();
        }
        */
        // TODO handle caching of structure loader
        $this->structureLoader->load();
    }

    public function isOptional()
    {
        return false;
    }
}
