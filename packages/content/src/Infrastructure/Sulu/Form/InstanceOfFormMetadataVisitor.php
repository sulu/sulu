<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Sulu\Form;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;

/**
 * Generic visitor that filters forms based on the instanceOf metadata option.
 *
 * This visitor can be used with any form type that has tagged forms with instanceOf attributes.
 * It dynamically loads and merges sub-forms that match the instanceOf class hierarchy.
 *
 * Multiple instances of this visitor can be configured, each with its own form registry
 * (e.g., one for excerpt forms, one for SEO forms, etc.).
 *
 * @internal
 */
class InstanceOfFormMetadataVisitor implements FormMetadataVisitorInterface
{
    /**
     * @param array<string, array{instanceOf: class-string, priority: int}> $forms
     */
    public function __construct(
        private XmlFormMetadataLoader $xmlFormMetadataLoader,
        private array $forms
    ) {
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if (!\array_key_exists('instanceOf', $metadataOptions)) {
            return;
        }

        /** @var class-string $instanceOf */
        $instanceOf = $metadataOptions['instanceOf'];

        $forms = $this->getMatchingForms($instanceOf);

        foreach ($forms as $form) {
            $subFormMetadata = $this->xmlFormMetadataLoader->getMetadata($form, $locale, $metadataOptions);

            if (!$subFormMetadata instanceof FormMetadata) {
                continue;
            }

            $formMetadata->setItems(\array_merge($formMetadata->getItems(), $subFormMetadata->getItems()));
            $formMetadata->setSchema($formMetadata->getSchema()->merge($subFormMetadata->getSchema()));
        }
    }

    /**
     * @param class-string $instanceOf
     *
     * @return string[]
     */
    private function getMatchingForms(string $instanceOf): array
    {
        $matchingForms = [];
        foreach ($this->forms as $key => $formConfig) {
            if (\is_a($instanceOf, $formConfig['instanceOf'], true)) {
                $matchingForms[] = $key;
            }
        }

        return $matchingForms;
    }
}
