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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\MediaBundle\Media\Classification\ClassificationProvider;

/**
 * Hides the classification field of the media details when no classifications are configured.
 *
 * @internal
 */
class ClassificationFormMetadataVisitor implements FormMetadataVisitorInterface
{
    public function __construct(private ClassificationProvider $classificationProvider)
    {
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ('media_details' !== $formMetadata->getKey() || $this->classificationProvider->hasClassifications()) {
            return;
        }

        $this->hideClassification($formMetadata->getItems());
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function hideClassification(array $items): void
    {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $this->hideClassification($item->getItems());
            } elseif ('classification' === $item->getName()) {
                $item->setVisibleCondition('false');
            }
        }
    }
}
