<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Admin\MetadataVisitor;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * @internal
 */
final class BlockSettingsFormMetadataVisitor implements FormMetadataVisitorInterface
{
    public function __construct(
        private readonly XmlFormMetadataLoader $xmlFormMetadataLoader,
        private WebspaceManagerInterface $webspaceManager,
    ) {
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ('content_block_settings' !== $formMetadata->getKey()) {
            return;
        }

        foreach ($formMetadata->getItems() as $item) {
            if ($item instanceof SectionMetadata && 'segments' === $item->getName()) {
                return;
            }
        }

        $segmentsForm = $this->xmlFormMetadataLoader->getMetadata('content_block_settings_segments', $locale, $metadataOptions);

        if (!$segmentsForm) {
            return;
        }

        $webspaces = $this->webspaceManager->getWebspaceCollection()->getWebspaces();
        $segments = 'false';

        foreach ($webspaces as $webspace) {
            if ($webspace->getSegments()) {
                $segments = 'true';
            }
        }

        foreach ($segmentsForm->getItems() as $item) {
            if (!($item instanceof SectionMetadata && 'segments' === $item->getName())) {
                continue;
            }

            $visibleCondition = $item->getVisibleCondition();

            if (!$visibleCondition) {
                continue;
            }

            $item->setVisibleCondition(\str_replace('true', $segments, $visibleCondition));
        }

        $formMetadata->setItems(\array_merge($formMetadata->getItems(), $segmentsForm->getItems()));
    }
}
