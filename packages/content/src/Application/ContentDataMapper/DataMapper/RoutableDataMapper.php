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

use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

class RoutableDataMapper implements DataMapperInterface
{
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private StructureMetadataFactoryInterface $factory,
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

        $metadata = $this->factory->getStructureMetadata($type, $template);
        if (!$metadata) {
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

        if (!\array_key_exists($name, $data)) {
            return;
        }

        $routeSlug = $data[$name];
        \assert(
            \is_string($routeSlug),
            \sprintf('Expected property "%s" be string but "%s" given.', $name, \get_debug_type($routeSlug))
        );

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
            // TODO support for parent and tree change update

            return;
        }

        $route = Route::createRouteWithTempId(
            $localizedDimensionContent::getResourceKey(),
            fn (): string => (string) $localizedDimensionContent->getResourceId(),
            $locale,
            $routeSlug,
            null, // TODO support for site / webspaces maybe set it in the `setRoute` of PageDimensionContent
            null, // TODO support for parent and tree route
        );

        $localizedDimensionContent->setRoute($route);

        $this->routeRepository->add($route);
    }

    private function getRouteProperty(StructureMetadata $metadata): ?PropertyMetadata
    {
        foreach ($metadata->getProperties() as $property) {
            // TODO add support for page_tree_route field type: https://github.com/sulu/SuluContentBundle/issues/242
            if ('route' === $property->getType()) {
                return $property;
            }
        }

        return null;
    }
}
