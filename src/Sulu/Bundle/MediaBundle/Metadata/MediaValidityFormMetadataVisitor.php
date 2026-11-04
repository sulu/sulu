<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;

/**
 * @internal
 */
class MediaValidityFormMetadataVisitor implements FormMetadataVisitorInterface
{
    public function __construct(private bool $validityEnabled)
    {
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ($this->validityEnabled || 'media_details' !== $formMetadata->getKey()) {
            return;
        }

        $detailsSection = $formMetadata->getItems()['media_details'] ?? null;
        $validitySection = $detailsSection instanceof SectionMetadata ? ($detailsSection->getItems()['validity'] ?? null) : null;

        $validitySection?->setVisibleCondition('false');
    }
}
