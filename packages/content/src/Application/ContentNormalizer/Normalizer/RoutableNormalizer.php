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

namespace Sulu\Content\Application\ContentNormalizer\Normalizer;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Route\Domain\Model\Route;

class RoutableNormalizer implements NormalizerInterface
{
    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
    ) {
    }

    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof RoutableInterface) {
            return $normalizedData;
        }

        if (!$object instanceof TemplateInterface) {
            return $normalizedData;
        }

        $locale = $object->getLocale();
        if (null === $locale) {
            return $normalizedData;
        }

        $template = $object->getTemplateKey();
        if (null === $template) {
            return $normalizedData;
        }

        $typedMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($object::getTemplateType(), $locale, []);
        if (!$typedMetadata instanceof TypedFormMetadata) {
            return $normalizedData;
        }

        $formMetadata = $typedMetadata->getForms()[$template] ?? null;
        if (!$formMetadata instanceof FormMetadata) {
            return $normalizedData;
        }

        foreach ($formMetadata->getFlatFieldMetadata() as $field) {
            $type = $field->getType();
            if ('route' !== $type && 'page_tree_route' !== $type) {
                continue;
            }
            $normalizedData[$field->getName()] = $this->resolveRouteFieldUrl($object, $type);
        }

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof RoutableInterface) {
            return [];
        }

        return [
            'resourceId',
            'route',
        ];
    }

    /**
     * @return string|array<string, mixed>|null
     */
    private function resolveRouteFieldUrl(RoutableInterface $dimensionContent, string $fieldType): string|array|null
    {
        $route = $dimensionContent->getRoute();
        if (null === $route) {
            return null;
        }

        if ('route' === $fieldType) {
            return $route->getSlug();
        }

        return $this->resolvePageTreeRoute($route);
    }

    /**
     * @return array{page: array{uuid: string, path: string}, suffix: string}|null
     */
    private function resolvePageTreeRoute(Route $route): ?array
    {
        $parentRoute = $route->getParentRoute();
        if (null === $parentRoute) {
            return null;
        }

        $parentSlug = $parentRoute->getSlug();
        $slug = $route->getSlug();
        $parentSlugTrimmed = \rtrim($parentSlug, '/');
        $suffix = \str_starts_with($slug, $parentSlugTrimmed)
            ? \substr($slug, \strlen($parentSlugTrimmed))
            : '';

        return [
            'page' => [
                'uuid' => $parentRoute->getResourceId(),
                'path' => $parentSlug,
            ],
            'suffix' => $suffix,
        ];
    }
}
