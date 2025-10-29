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

namespace Sulu\CustomUrl\Infrastructure\Symfony\Serializer;

use Sulu\CustomUrl\Domain\Model\CustomUrlRouteInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class CustomUrlRouteNormalizer implements NormalizerInterface
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
        if (!\array_key_exists(AbstractNormalizer::IGNORED_ATTRIBUTES, $context)) {
            $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = [];
        }

        \assert(\is_array($context[AbstractNormalizer::IGNORED_ATTRIBUTES]));

        // Ignore circular reference fields
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES][] = 'customUrl';
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES][] = 'targetRoute';
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
        return $data instanceof CustomUrlRouteInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [CustomUrlRouteInterface::class => true];
    }
}
