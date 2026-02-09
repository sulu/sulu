<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Infrastructure\Sulu\Admin\MetadataVisitor;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;

class BlockSettingsFormMetadataVisitor implements FormMetadataVisitorInterface
{
    public function __construct(private readonly XmlFormMetadataLoader $xmlFormMetadataLoader)
    {
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ('content_block_settings' !== $formMetadata->getKey()) {
            return;
        }

        foreach ($formMetadata->getItems() as $item) {
            if ($item instanceof SectionMetadata && 'target_groups' === $item->getName()) {
                return;
            }
        }

        $segmentsForm = $this->xmlFormMetadataLoader->getMetadata('content_block_settings_target_groups', $locale, $metadataOptions);

        if (!$segmentsForm) {
            return;
        }

        $formMetadata->setItems(\array_merge($formMetadata->getItems(), $segmentsForm->getItems()));
    }
}
