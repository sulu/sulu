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

use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetArea;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
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
        $snippetDimensionContent = $this->resolveDimensionContent($snippet, $locale);
        $normalizedData['snippetTitle'] = $snippetDimensionContent?->getTitle();
        $normalizedData['snippetUuid'] = $snippet?->getId();
        // required by the frontend to resolve the group-specific edit view after assigning a snippet
        $normalizedData['templateKey'] = $snippetDimensionContent?->getTemplateKey();
        $normalizedData['title'] = $title;

        // Why would this would be false?
        $normalizedData['valid'] = true;

        return $normalizedData;
    }

    private function resolveDimensionContent(?SnippetInterface $snippet, string $locale): ?SnippetDimensionContentInterface
    {
        if (null === $snippet) {
            return null;
        }

        /** @var DimensionContentCollection<SnippetDimensionContentInterface> $dimensionContentCollection */
        $dimensionContentCollection = new DimensionContentCollection(
            $snippet->getDimensionContents(),
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            $snippet->createDimensionContent()::class
        );

        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $locale]);
        if (null !== $localizedDimensionContent) {
            return $localizedDimensionContent;
        }

        $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => null]);
        if (null === $unlocalizedDimensionContent) {
            return null;
        }

        $ghostLocale = $unlocalizedDimensionContent->getGhostLocale() ?? $unlocalizedDimensionContent->getAvailableLocales()[0] ?? null;

        return $dimensionContentCollection->getDimensionContent(['locale' => $ghostLocale]);
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
