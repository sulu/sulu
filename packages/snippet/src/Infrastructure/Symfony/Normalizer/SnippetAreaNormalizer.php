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

use Sulu\Snippet\Domain\Model\SnippetArea;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Symfony\CompilerPass\SnippetAreaCompilerPass;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @phpstan-import-type SnippetAreaconfig from SnippetAreaCompilerPass
 */
final class SnippetAreaNormalizer implements NormalizerInterface
{
    /**
     * @param SnippetAreaconfig $snippetAreas
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

        /** @var array<mixed> $normalizedData */
        $normalizedData = $this->objectNormalizer->normalize($data, $format, $context);

        $data->setSnippet($snippet);

        unset($normalizedData['snippet']);

        $metaData = $this->snippetAreas[$normalizedData['areaKey']];
        $title = $metaData['title'][$context['locale']];

        // Remove ids because that's an implementation detail
        unset($normalizedData['id'], $normalizedData['uuid']);

        $normalizedData['key'] = $normalizedData['areaKey'];
        unset($normalizedData['areaKey'], $normalizedData['webspaceKey']);

        /** @var SnippetInterface|null $snippet */
        $snippet = $data->getSnippet();
        $normalizedData['defaultTitle'] = $this->getTitle($snippet);
        $normalizedData['defaultUuid'] = $snippet?->getId();
        $normalizedData['title'] = $title;

        // Why would this would be false?
        $normalizedData['valid'] = true;

        return $normalizedData;
    }

    private static function getTitle(?SnippetInterface $snippet): ?string
    {
        if (null === $snippet) {
            return null;
        }

        // TODO: Currently gets the first title in any locale. We need to load the locale of the request here.
        // Also: Use a repository for this for more performance
        $dim = $snippet->getDimensionContents()
            ->map(fn ($snippet) => $snippet->getTitle())
            ->filter(fn ($snippet) => null !== $snippet)
            ->first()
        ;

        return $dim ?: 'unknown';
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
