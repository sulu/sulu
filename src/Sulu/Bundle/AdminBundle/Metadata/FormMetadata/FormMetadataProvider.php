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
            $this->evaluateFormItemExpressions($form->getItems(), $metadataOptions);
        } elseif ($form instanceof TypedFormMetadata) {
            foreach ($form->getForms() as $formType) {
                $this->evaluateFormItemExpressions($formType->getItems(), $metadataOptions);
            }

            if (\array_key_exists('tags', $metadataOptions)) {
                foreach ($metadataOptions['tags'] as $tagName => $tagAttributes) {
                    if (!\is_array($tagAttributes)) {
                        $tagAttributes = \filter_var($tagAttributes, \FILTER_VALIDATE_BOOLEAN);
                    }

                    $this->filterByTag($form, $tagName, $tagAttributes);
                }
            }
        }

        return $form;
    }

    /**
     * @param array|bool $tagAttributes
     */
    private function filterByTag(TypedFormMetadata $form, string $tagName, $tagAttributes): void
    {
        foreach ($form->getForms() as $formKey => $childForm) {
            if (!$this->matchFormAgainstTag($childForm, $tagName, $tagAttributes)) {
                $form->removeForm($formKey);
            }
        }
    }

    /**
     * @param array|bool $tagAttributes
     */
    private function matchFormAgainstTag(FormMetadata $form, string $tagName, $tagAttributes): bool
    {
        $tags = $form->getTagsByName($tagName);
        if (\is_bool($tagAttributes)) {
            return ($tagAttributes && 0 !== \count($tags)) || (!$tagAttributes && 0 === \count($tags));
        }

        if (0 === \count($tags)) {
            return false;
        }

        foreach ($tags as $tag) {
            if (!$tag->hasAttributes($tagAttributes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function evaluateFormItemExpressions(array $items, array $metadataOptions)
    {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $this->evaluateFormItemExpressions($item->getItems(), $metadataOptions);
            }

            if ($item instanceof FieldMetadata) {
                foreach ($item->getTypes() as $type) {
                    $this->evaluateFormItemExpressions($type->getItems(), $metadataOptions);
                }

                foreach ($item->getOptions() as $option) {
                    if (OptionMetadata::TYPE_EXPRESSION === $option->getType()) {
                        $option->setValue($this->expressionLanguage->evaluate($option->getValue(), $metadataOptions));
                    }
                }
            }
        }
    }
}
