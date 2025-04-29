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

namespace Sulu\Content\Infrastructure\Sulu\Traits;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;

/**
 * @internal
 */
trait ResolveContentDimensionUrlTrait
{
    /**
     * @template C of DimensionContentInterface
     *
     * @param C $dimensionContent
     * @param mixed[] $data
     */
    protected function getUrl(DimensionContentInterface $dimensionContent, array $data): ?string
    {
        if (!$dimensionContent instanceof TemplateInterface) {
            // TODO FIXME add testcase for it
            return null; // @codeCoverageIgnore
        }

        $type = $dimensionContent::getTemplateType();
        $template = $dimensionContent->getTemplateKey();

        $metadata = $this->getMetadataProviderRegistry()->getMetadataProvider('form')
            ->getMetadata($type, $dimensionContent->getLocale() ?? 'en', []);

        if (!$metadata instanceof TypedFormMetadata) {
            // TODO FIXME add testcase for it
            return null; // @codeCoverageIgnore
        }

        $metadata = $metadata->getForms()[$template] ?? null;

        if (!$metadata instanceof FormMetadata) {
            // TODO FIXME add testcase for it
            return null; // @codeCoverageIgnore
        }

        foreach ($metadata->getItems() as $property) {
            if ('route' === $property->getType() || 'resource_locator' === $property->getType()) {
                /** @var string|null */
                return $dimensionContent->getTemplateData()[$property->getName()] ?? null;
            }
        }

        return null;
    }

    abstract protected function getMetadataProviderRegistry(): MetadataProviderRegistry;
}
