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
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class FormMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var FormMetadataLoaderInterface[]
     */
    private $formMetadataLoaders;

    public function __construct(
        ExpressionLanguage $expressionLanguage,
        iterable $formMetadataLoaders
    ) {
        $this->expressionLanguage = $expressionLanguage;
        $this->formMetadataLoaders = $formMetadataLoaders;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = []): MetadataInterface
    {
        $form = null;
        foreach ($this->formMetadataLoaders as $metadataLoader) {
            $form = $metadataLoader->getMetadata($key, $locale, $metadataOptions);
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

            if (array_key_exists('tags', $metadataOptions)) {
                $tags = $metadataOptions['tags'];
                $tagAttributes = $metadataOptions['tagAttributes'] ?? [];
                foreach ($tags as $tagName => $tagValue) {
                    $this->filterByTag($form, $tagName, $tagValue, $tagAttributes[$tagName] ?? 'value');
                }
            }
        }

        return $form;
    }

    private function filterByTag(TypedFormMetadata $form, string $tagName, string $tagValue, string $tagAttribute): void
    {
        foreach ($form->getForms() as $formKey => $childForm) {
            $tag = $childForm->getTag($tagName);
            $actualTagValue = $tag ? $tag->getAttribute($tagAttribute) : '';

            if ($actualTagValue !== $tagValue) {
                $form->removeForm($formKey);
            }
        }
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
}
