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

namespace Sulu\Content\Infrastructure\Sulu\Traits;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;

/**
 * @internal
 *
 * @deprecated since 3.0.3, implement URL resolution directly in your teaser provider
 */
trait ResolveContentDimensionUrlTrait
{
    /**
     * @template C of DimensionContentInterface
     *
     * @param C $dimensionContent
     * @param mixed[] $data
     */
    protected function getUrl(DimensionContentInterface $dimensionContent, array $data): ?string
    {
        if ($dimensionContent instanceof RoutableInterface) {
            $route = $dimensionContent->getRoute();
            $routeGenerator = $this->getRouteGenerator();
            if (null !== $route) {
                return $routeGenerator->generate(
                    $route->getSlug(),
                    $route->getLocale(),
                    $route->getWebspace() ?? $route->getParentRoute()?->getWebspace(),
                );
            }
        }

        if (!$dimensionContent instanceof TemplateInterface) {
            // TODO FIXME add testcase for it
            return null; // @codeCoverageIgnore
        }

        $type = $dimensionContent::getTemplateType();
        $template = $dimensionContent->getTemplateKey();

        $metadata = $this->getMetadataProviderRegistry()->getMetadataProvider('form')
            ->getMetadata($type, $dimensionContent->getLocale() ?? 'en', []);

        if (!$metadata instanceof TypedFormMetadata) {
            // TODO FIXME add testcase for it
            return null; // @codeCoverageIgnore
        }

        $metadata = $metadata->getForms()[$template] ?? null;

        if (!$metadata instanceof FormMetadata) {
            // TODO FIXME add testcase for it
            return null; // @codeCoverageIgnore
        }

        foreach ($metadata->getFlatFieldMetadata() as $property) {
            if ('route' === $property->getType()) {
                /** @var string|null */
                return $dimensionContent->getTemplateData()[$property->getName()] ?? null;
            }

            if ('page_tree_route' === $property->getType()) {
                /** @var array{page?: array{path?: string}, suffix?: string}|null $pageTreeRoute */
                $pageTreeRoute = $dimensionContent->getTemplateData()[$property->getName()] ?? null;
                if (\is_array($pageTreeRoute) && isset($pageTreeRoute['page']['path'], $pageTreeRoute['suffix'])) {
                    return \rtrim($pageTreeRoute['page']['path'], '/') . '/' . \ltrim($pageTreeRoute['suffix'], '/');
                }
            }
        }

        return null;
    }

    abstract protected function getMetadataProviderRegistry(): MetadataProviderRegistry;

    abstract protected function getRouteGenerator(): RouteGeneratorInterface;
}
