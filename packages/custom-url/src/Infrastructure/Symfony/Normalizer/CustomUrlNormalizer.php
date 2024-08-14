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

namespace Sulu\CustomUrl\Infrastructure\Symfony\Normalizer;

use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class CustomUrlNormalizer implements NormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $objectNormalizer
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
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES] ??= [];
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES][] = 'created';
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES][] = 'changed';
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES][] = 'creator';
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES][] = 'changer';

        /** @var array<mixed> $normalizedData */
        $normalizedData = $this->objectNormalizer->normalize($data, $format, $context);

        return $normalizedData;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CustomUrlInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [CustomUrlInterface::class => true];
    }
}
