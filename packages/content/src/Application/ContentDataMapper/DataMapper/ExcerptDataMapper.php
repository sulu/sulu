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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;

readonly class ExcerptDataMapper implements DataMapperInterface
{
    /**
     * @param array<string, array{instanceOf: class-string}> $excerptForms
     */
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
        private array $excerptForms,
    ) {
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): void {
        if (!$localizedDimensionContent instanceof ExcerptInterface) {
            return;
        }

        $excerptData = $localizedDimensionContent->getExcerptData();
        $validExcerptProperties = $this->getExcerptProperties($localizedDimensionContent);
        foreach ($data as $key => $value) {
            if (($validExcerptProperties[$key] ?? null) !== null && \str_starts_with($key, 'excerpt')) {
                $internalKey = \lcfirst(\substr($key, 7));
                $excerptData[$internalKey] = $value;
            }
        }

        $localizedDimensionContent->setExcerptData($excerptData);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     *
     * @return array<string, mixed>
     */
    private function getExcerptProperties(DimensionContentInterface $dimensionContent): array
    {
        $locale = $dimensionContent->getLocale();
        if (!$locale) {
            return [];
        }

        $forms = $this->getExcerptForms();
        if (0 === \count($forms)) {
            return [];
        }

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata('content_excerpt', $locale, ['forms' => $forms]);

        return $formMetadata->getFlatFieldMetadata();
    }

    /**
     * @return string[]
     */
    private function getExcerptForms(): array
    {
        $forms = [];
        foreach ($this->excerptForms as $key => $tag) {
            if (ExcerptInterface::class === $tag['instanceOf']) {
                $forms[] = $key;
            }
        }

        return $forms;
    }
}
