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
use Sulu\Bundle\MediaBundle\Media\Classification\ClassificationProvider;

/**
 * Without configured classifications, the content locales field takes the full row the classification field leaves.
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

        $fields = $formMetadata->getFlatFieldMetadata();
        ($fields['classification'] ?? null)?->setVisibleCondition('false');
        ($fields['contentLocales'] ?? null)?->setColSpan(12);
    }
}
