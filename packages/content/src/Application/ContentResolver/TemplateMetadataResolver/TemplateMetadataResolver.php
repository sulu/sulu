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

namespace Sulu\Content\Application\ContentResolver\TemplateMetadataResolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;

/**
 * @phpstan-type TemplateMetadata array{
 *     key: string|null,
 *     group: string|null,
 *     tags: array<array{
 *         name: string,
 *         priority: int|null,
 *         ...<string, mixed>
 *     }>
 * }
 */
readonly class TemplateMetadataResolver implements ResolverInterface
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        if (!$dimensionContent instanceof TemplateInterface) {
            return null;
        }

        $formMetadata = $this->getFormMetadata($dimensionContent);

        $tags = [];
        foreach ($formMetadata?->getTags() ?? [] as $tag) {
            $tags[] = [
                'name' => $tag->getName(),
                'priority' => $tag->getPriority(),
                ...$tag->getAttributes(),
            ];
        }

        /** @var array{template: TemplateMetadata} $result */
        $result = [
            'template' => [
                'key' => $dimensionContent->getTemplateKey(),
                'group' => $formMetadata?->getGroup(),
                'tags' => $tags,
            ],
        ];

        return ContentView::create($result, []);
    }

    public function getType(): string
    {
        return 'templateMetadata';
    }

    /**
     * Merges into the root like the settings, whose "template" it replaces by a higher tag priority.
     */
    public function getOutputPath(): ?string
    {
        return '';
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param TemplateInterface&DimensionContentInterface<T> $dimensionContent
     */
    private function getFormMetadata(TemplateInterface&DimensionContentInterface $dimensionContent): ?FormMetadata
    {
        $templateKey = $dimensionContent->getTemplateKey();
        $locale = $dimensionContent->getLocale();
        if (null === $templateKey || null === $locale) {
            return null;
        }

        /** @var TypedFormMetadata $typedFormMetadata */
        $typedFormMetadata = $this->formMetadataProvider->getMetadata($dimensionContent::getTemplateType(), $locale, []);

        return $typedFormMetadata->getForms()[$templateKey] ?? null;
    }
}
