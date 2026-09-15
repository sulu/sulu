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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

class RoutableDataMapper implements DataMapperInterface
{
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private MetadataProviderRegistry $metadataProviderRegistry,
    ) {
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof RoutableInterface) {
            return;
        }

        if (!$localizedDimensionContent instanceof TemplateInterface) {
            throw new \RuntimeException('LocalizedDimensionContent needs to extend the TemplateInterface.');
        }

        $type = $localizedDimensionContent::getTemplateType();

        /** @var string|null $template */
        $template = $localizedDimensionContent->getTemplateKey() ?? null;

        if (null === $template) {
            throw new \RuntimeException('LocalizedDimensionContent should return the a template.');
        }

        $locale = $localizedDimensionContent->getLocale();

        if (!$locale) {
            throw new \RuntimeException('Expected a LocalizedDimensionContent with a locale.');
        }

        $typedMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($type, $locale, []);

        if (!$typedMetadata instanceof TypedFormMetadata) {
            throw new \RuntimeException(\sprintf('Could not find metadata "%s" of type "%s".', 'form', $type));
        }

        $metadata = $typedMetadata->getForms()[$template] ?? null;

        if (!$metadata instanceof FormMetadata) {
            return;
        }

        $property = $this->getRouteProperty($metadata);

        if (!$property) {
            return;
        }

        $locale = $localizedDimensionContent->getLocale();
        if (!$locale) {
            throw new \RuntimeException('Expected a LocalizedDimensionContent with a locale.');
        }

        /** @var string $name */
        $name = $property->getName();
        if ('url' !== $name) {
            throw new \RuntimeException(\sprintf(
                'Expected a property with the name "url" but "%s" given.',
                $name
            )); // TODO move this validation to a compiler pass see also direct access of 'url' in PublishTransitionSubscriber class.
        }

        if (!$this->hasRouteData($property, $data[$name] ?? null)) {
            if (\array_key_exists($name, $data) && $localizedDimensionContent::isRouteMandatory()) {
                throw new \RuntimeException(\sprintf(
                    'Expected a route value for the mandatory property "%s".',
                    $name
                ));
            }

            return;
        }

        $parentRoute = $this->findParentRoute($property, $data[$name], $locale);
        $routeSlug = $this->extractRouteSlug($property, $data[$name], $parentRoute);

        $route = $localizedDimensionContent->getRoute();
        if (!$route instanceof Route
            && DimensionContentInterface::STAGE_DRAFT !== $localizedDimensionContent->getStage()
        ) {
            $route = $this->routeRepository->findOneBy([
                'locale' => $locale,
                'resourceKey' => $localizedDimensionContent::getResourceKey(),
                'resourceId' => (string) $localizedDimensionContent->getResourceId(),
            ]);

            if (!$route instanceof Route) {
                throw new \RuntimeException(\sprintf(
                    'Expected that the draft dimension of "%s" with id "%s" and locale "%s" already created the route.',
                    $localizedDimensionContent::getResourceKey(),
                    $localizedDimensionContent->getResourceId(),
                    $locale,
                ));
            }

            $localizedDimensionContent->setRoute($route);

            return;
        }

        if ($route instanceof Route) {
            $route->setSlug($routeSlug);
            $route->setParentRoute($parentRoute);

            return;
        }

        $route = Route::createRouteWithTempId(
            $localizedDimensionContent::getResourceKey(),
            fn (): string => (string) $localizedDimensionContent->getResourceId(),
            $locale,
            $routeSlug,
            null,
            $parentRoute,
        );

        $localizedDimensionContent->setRoute($route);

        $this->routeRepository->add($route);
    }

    private function getRouteProperty(FormMetadata $metadata): ?FieldMetadata
    {
        foreach ($metadata->getFlatFieldMetadata() as $property) {
            if ('route' === $property->getType()
                || 'page_tree_route' === $property->getType()
            ) {
                return $property;
            }
        }

        return null;
    }

    private function hasRouteData(FieldMetadata $property, mixed $routeData): bool
    {
        if (null === $routeData) {
            return false;
        }

        if ('page_tree_route' === $property->getType()) {
            if (!\is_array($routeData)) {
                throw new \RuntimeException(\sprintf(
                    'Expected property "%s" to be an array or null but "%s" given.',
                    $property->getName(),
                    \get_debug_type($routeData)
                ));
            }

            $pageData = $routeData['page'] ?? null;
            $pageUuid = \is_array($pageData) ? ($pageData['uuid'] ?? null) : null;
            $pagePath = \is_array($pageData) ? ($pageData['path'] ?? null) : null;

            // the admin field submits an empty page (e.g. {"page": {}}) when no parent page is selected yet
            return \is_string($pageUuid) && '' !== $pageUuid
                && \is_string($pagePath) && '' !== $pagePath
                && \is_string($routeData['suffix'] ?? null);
        }

        if (!\is_string($routeData)) {
            throw new \RuntimeException(\sprintf(
                'Expected property "%s" to be a string or null but "%s" given.',
                $property->getName(),
                \get_debug_type($routeData)
            ));
        }

        return '' !== $routeData;
    }

    private function extractRouteSlug(FieldMetadata $property, mixed $routeData, ?Route $parentRoute): string
    {
        if ('page_tree_route' === $property->getType()) {
            \assert(
                \is_array($routeData),
                \sprintf('Expected property "%s" be array object but "%s" given.', $property->getName(), \get_debug_type($routeData))
            );

            $pageData = $routeData['page'] ?? null;
            \assert(
                \is_array($pageData),
                \sprintf('Expected property "%s/page" be an array but "%s" given.', $property->getName(), \get_debug_type($pageData))
            );

            $pagePath = $parentRoute?->getSlug() ?? $pageData['path'] ?? null;
            \assert(
                \is_string($pagePath),
                \sprintf('Expected property "%s/page/path" be string but "%s" given.', $property->getName(), \get_debug_type($pagePath))
            );

            $suffix = $routeData['suffix'] ?? null;
            \assert(
                \is_string($suffix),
                \sprintf('Expected property "%s/suffix" be string but "%s" given.', $property->getName(), \get_debug_type($suffix))
            );

            return \rtrim($pagePath, '/') . '/' . \ltrim($suffix, '/');
        }

        \assert(
            \is_string($routeData),
            \sprintf('Expected property "%s" be string but "%s" given.', $property->getName(), \get_debug_type($routeData))
        );

        return $routeData;
    }

    private function findParentRoute(FieldMetadata $property, mixed $routeData, string $locale): ?Route
    {
        if ('page_tree_route' === $property->getType()) {
            \assert(
                \is_array($routeData),
                \sprintf('Expected property "%s" be array object but "%s" given.', $property->getName(), \get_debug_type($routeData))
            );

            $pageData = $routeData['page'] ?? null;
            \assert(
                \is_array($pageData),
                \sprintf('Expected property "%s/page" be an array but "%s" given.', $property->getName(), \get_debug_type($pageData))
            );

            $pageUuid = $pageData['uuid'] ?? null;
            \assert(
                \is_string($pageUuid),
                \sprintf('Expected property "%s/page/path" be string but "%s" given.', $property->getName(), \get_debug_type($pageUuid))
            );

            return $this->routeRepository->findOneBy([
                'locale' => $locale,
                'resourceKey' => 'pages', // not using constant to avoid dependency to page bundle
                'resourceId' => $pageUuid,
            ]);
        }

        return null;
    }
}
