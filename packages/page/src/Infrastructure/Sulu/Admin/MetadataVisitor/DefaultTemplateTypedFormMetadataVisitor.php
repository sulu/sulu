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

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadataVisitorInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * @internal no backwards compatibility promise is given for this class it could be removed or changed at any time.
 *           create your own service based on `TypedFormMetadataVisitorInterface` to customize the behavior if needed.
 */
final class DefaultTemplateTypedFormMetadataVisitor implements TypedFormMetadataVisitorInterface
{
    public function __construct(private WebspaceManagerInterface $webspaceManager)
    {
    }

    /**
     * @param array{webspace?: string} $metadataOptions
     */
    public function visitTypedFormMetadata(TypedFormMetadata $formMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        $webspaceKey = $metadataOptions['webspace'] ?? null;
        if (null === $webspaceKey) {
            return;
        }

        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        if (null === $webspace) {
            return;
        }

        $excludedTemplates = $webspace->getExcludedTemplates();
        foreach ($excludedTemplates as $excludedTemplate) {
            $formMetadata->removeForm($excludedTemplate);
        }

        $defaultTemplate = $webspace->getDefaultTemplate('page');
        if (null === $defaultTemplate) {
            return;
        }
        $formMetadata->setDefaultType($defaultTemplate);
    }
}
