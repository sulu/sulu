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

namespace Sulu\Content\Application\ContentResolver\Resolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\LinkInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Route\Domain\Model\Route;

/**
 * Decorates the template resolver to derive route field URLs from the Route entity
 * for content implementing RoutableInterface.
 */
readonly class RoutableTemplateResolver implements ResolverInterface
{
    public function __construct(
        private ResolverInterface $inner,
        private MetadataProviderInterface $formMetadataProvider,
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        if (!$dimensionContent instanceof RoutableInterface || !$dimensionContent instanceof TemplateInterface) {
            return $this->inner->resolve($dimensionContent, $properties);
        }

        // Linked content is rendered through its target's URL (resolved by the link
        // provider), so the local Route entity must not overwrite the URL stored in
        // templateData for those dimension contents.
        if ($this->shouldSkipRouteFieldOverwrite($dimensionContent)) {
            return $this->inner->resolve($dimensionContent, $properties);
        }

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();
        $templateKey = $dimensionContent->getTemplateKey();
        $templateType = $dimensionContent::getTemplateType();

        /** @var TypedFormMetadata $typedFormMetadata */
        $typedFormMetadata = $this->formMetadataProvider->getMetadata($templateType, $locale, []);
        $formMetadata = $typedFormMetadata->getForms()[$templateKey] ?? null;

        if (null !== $formMetadata) {
            $data = $dimensionContent->getTemplateData();
            foreach ($formMetadata->getFlatFieldMetadata() as $name => $field) {
                $type = $field->getType();
                if ('route' !== $type && 'page_tree_route' !== $type) {
                    continue;
                }

                $data[$name] = $this->resolveRouteFieldUrl($dimensionContent, $type);
            }
            // The Route entity is the source of truth for the URL: route fields are
            // not persisted into templateData by the TemplateDataMapper, so we fill
            // them here from the Route entity before delegating to the inner resolver.
            $dimensionContent->setTemplateData($data);
        }

        return $this->inner->resolve($dimensionContent, $properties);
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     */
    private function shouldSkipRouteFieldOverwrite(DimensionContentInterface $dimensionContent): bool
    {
        if (!$dimensionContent instanceof LinkInterface) {
            return false;
        }

        $linkData = $dimensionContent->getLinkData();

        return null !== ($linkData['provider'] ?? null);
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
        $suffix = \str_starts_with($slug, $parentSlug)
            ? \substr($slug, \strlen($parentSlug))
            : '';

        return [
            'page' => [
                'uuid' => $parentRoute->getResourceId(),
                'path' => $parentSlug,
            ],
            'suffix' => $suffix,
        ];
    }

    public static function getType(): string
    {
        return 'template';
    }

    public static function getOutputPath(): string
    {
        return '[root][content]';
    }
}
