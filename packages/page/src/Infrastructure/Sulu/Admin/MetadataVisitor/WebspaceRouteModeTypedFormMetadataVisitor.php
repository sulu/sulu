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

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadataVisitorInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Page\Domain\Model\PageInterface;

/**
 * @internal no backwards compatibility promise is given for this class it could be removed or changed at any time.
 *           create your own TypedFormMetadataVisitorInterface service to customize the behavior if needed.
 */
final class WebspaceRouteModeTypedFormMetadataVisitor implements TypedFormMetadataVisitorInterface
{
    public function __construct(private readonly WebspaceManagerInterface $webspaceManager)
    {
    }

    /**
     * @param mixed[] $metadataOptions
     */
    public function visitTypedFormMetadata(TypedFormMetadata $formMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        if (PageInterface::TEMPLATE_TYPE !== $key) {
            return;
        }

        $webspaceKey = $metadataOptions['webspace'] ?? null;

        foreach ($formMetadata->getForms() as $formMetadata) {
            $this->searchAndEnhanceRouteProperty($formMetadata->getItems(), $webspaceKey);
        }
    }

    /**
     * @param ItemMetadata[] $itemsMetadata
     */
    private function searchAndEnhanceRouteProperty(array $itemsMetadata, string $webspaceKey): void
    {
        foreach ($itemsMetadata as $itemMetadata) {
            if ($itemMetadata instanceof SectionMetadata) {
                $this->searchAndEnhanceRouteProperty($itemMetadata->getItems(), $webspaceKey);

                continue;
            }

            if (!$itemMetadata instanceof FieldMetadata) {
                continue;
            }

            foreach ($itemMetadata->getTypes() as $type) {
                $this->searchAndEnhanceRouteProperty($type->getItems(), $webspaceKey);
            }

            if ('route' !== $itemMetadata->getType()) {
                continue;
            }

            if (\array_key_exists('mode', $itemMetadata->getOptions())) {
                continue;
            }

            $routeMode = $this->getModeForWebspace($webspaceKey);

            $optionMetadata = new OptionMetadata();
            $optionMetadata->setName('mode');
            $optionMetadata->setValue($routeMode);
            $itemMetadata->addOption($optionMetadata);

            break;
        }
    }

    private function getModeForWebspace(string $webspaceKey): string
    {
        $webspace = $this->webspaceManager->getWebspaceCollection()
            ->getWebspace($webspaceKey);

        \assert(null !== $webspace, \sprintf('The webspace with key "%s" does not exist.', $webspaceKey));

        return $webspace->getResourceLocatorStrategy();
    }
}
