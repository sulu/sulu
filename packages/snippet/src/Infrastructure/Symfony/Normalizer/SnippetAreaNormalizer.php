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

namespace Sulu\Snippet\Infrastructure\Symfony\Normalizer;

use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetArea;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Symfony\CompilerPass\SnippetAreaCompilerPass;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @phpstan-import-type SnippetAreaConfig from SnippetAreaCompilerPass
 */
final class SnippetAreaNormalizer implements NormalizerInterface
{
    /**
     * @param SnippetAreaConfig $snippetAreas
     */
    public function __construct(
        private ObjectNormalizer $objectNormalizer,
        private ContentAggregatorInterface $contentAggregator,
        private array $snippetAreas,
    ) {
    }

    /**
     * @param SnippetArea $data
     *
     * @return array<mixed>
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array {
        /** @var SnippetAreaInterface $data */
        $snippet = $data->getSnippet();
        if (null !== $snippet) {
            $data->setSnippet(null);
        }

        $locale = $context['locale'] ?? null;
        if (null === $locale || !\is_string($locale)) {
            throw new \InvalidArgumentException('The "locale" context parameter is required and must be a string.');
        }

        /** @var array<mixed> $normalizedData */
        $normalizedData = $this->objectNormalizer->normalize($data, $format, $context);

        $data->setSnippet($snippet);

        unset($normalizedData['snippet']);

        $areaKey = $normalizedData['areaKey'] ?? null;
        if (!\is_string($areaKey) || !isset($this->snippetAreas[$areaKey])) {
            throw new \InvalidArgumentException('Invalid or missing areaKey.');
        }

        $metaData = $this->snippetAreas[$areaKey];
        $title = $metaData['title'][$locale] ?? '';

        // Remove ids because that's an implementation detail
        unset($normalizedData['id'], $normalizedData['uuid']);

        $normalizedData['key'] = $normalizedData['areaKey'];
        unset($normalizedData['areaKey'], $normalizedData['webspaceKey']);

        /** @var SnippetInterface|null $snippet */
        $snippet = $data->getSnippet();
        $normalizedData['snippetTitle'] = $this->getTitle($snippet, $locale);
        $normalizedData['snippetUuid'] = $snippet?->getId();
        $normalizedData['title'] = $title;

        // Why would this would be false?
        $normalizedData['valid'] = true;

        return $normalizedData;
    }

    private function getTitle(?SnippetInterface $snippet, string $locale): ?string
    {
        if (null === $snippet) {
            return null;
        }

        $dimensionContent = $this->contentAggregator->aggregate(
            $snippet,
            [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ]
        );

        return $dimensionContent->getTitle();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof SnippetAreaInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [SnippetAreaInterface::class => true];
    }
}
