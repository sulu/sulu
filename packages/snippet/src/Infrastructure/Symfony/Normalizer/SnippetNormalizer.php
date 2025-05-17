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

use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class SnippetNormalizer implements NormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $objectNormalizer,
        private array $snippetArea,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array {
        /** @var array<mixed> $normalizedData */
        $normalizedData = $this->objectNormalizer->normalize($data, $format, $context);

        dd($this->snippetArea[$normalizedData['areaKey']]);

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
