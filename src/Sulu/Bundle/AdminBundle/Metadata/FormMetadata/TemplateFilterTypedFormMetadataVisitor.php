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

/**
 * @internal This class is internal. Create a separate visitor if you want to manipulate the metadata in your project.
 */
class TemplateFilterTypedFormMetadataVisitor implements TypedFormMetadataVisitorInterface
{
    public static function getDefaultPriority(): int
    {
        return -100;
    }

    public function visitTypedFormMetadata(TypedFormMetadata $formMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        if (!\array_key_exists('templates', $metadataOptions)) {
            return;
        }

        $templates = $metadataOptions['templates'];
        if (!\is_array($templates)) {
            if (!\is_string($templates)) {
                return; // Skip if not string or array
            }
            $templates = \array_filter(\explode(',', $templates));
        }

        foreach ($formMetadata->getForms() as $formKey => $childForm) {
            if (!\in_array($formKey, $templates, true)) {
                $formMetadata->removeForm($formKey);
            }
        }
    }
}
