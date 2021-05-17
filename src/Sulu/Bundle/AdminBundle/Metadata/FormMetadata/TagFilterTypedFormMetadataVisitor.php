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

class TagFilterTypedFormMetadataVisitor implements TypedFormMetadataVisitorInterface
{
    public static function getDefaultPriority(): int
    {
        return -100;
    }

    public function visitTypedFormMetadata(TypedFormMetadata $formMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        if (\array_key_exists('tags', $metadataOptions)) {
            foreach ($metadataOptions['tags'] as $tagName => $tagAttributes) {
                if (!\is_array($tagAttributes)) {
                    $tagAttributes = \filter_var($tagAttributes, \FILTER_VALIDATE_BOOLEAN);
                }

                $this->filterByTag($formMetadata, $tagName, $tagAttributes);
            }
        }
    }

    /**
     * @param mixed[]|bool $tagAttributes
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
     * @param mixed[]|bool $tagAttributes
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
}
