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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class SnippetNormalizer implements NormalizerInterface
{
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
        /** @var array<mixed> $normalizedData */
        $normalizedData = $this->objectNormalizer->normalize($data, $format, $context);
        unset($normalizedData['snippet']);

        $metaData = $this->snippetAreas[$normalizedData['areaKey']];
        $title = $metaData['title'][$context['locale']];

        // Remove ids because that's an implementation detail
        unset($normalizedData['id'], $normalizedData['uuid']);

        $normalizedData['key'] = $normalizedData['areaKey'];
        unset($normalizedData['areaKey'], $normalizedData['webspaceKey']);

        /** @var SnippetInterface $snippet */
        $snippet = $data->getSnippet();
        $normalizedData['defaultTitle'] = $snippet?->getTitle();
        $normalizedData['defaultUuid'] = $snippet?->getId();
        $normalizedData['template'] = $metaData['template'];
        $normalizedData['title'] = $title;

        // Why would this would be false?
        $normalizedData['valid'] = true;

        return $normalizedData;
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
