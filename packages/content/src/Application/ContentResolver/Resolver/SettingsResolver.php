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

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\Reference;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\Routing\RequestContext;

/**
 * @phpstan-type SettingsData array{
 *      availableLocales?: string[]|null,
 *      localizations?: array<string, array{
 *          locale: string,
 *          url: string|null,
 *          country: string,
 *          alternate: bool
 *      }>,
 *      mainWebspace?: string|null,
 *      template?: string|null,
 *      author?: ContentView|null,
 *      authored?: \DateTime|null,
 *      shadowBaseLocale?: string|null,
 *      lastModified?: \DateTimeImmutable|null
 *  }
 */
readonly class SettingsResolver implements ResolverInterface
{
    public function __construct(
        private RouteGeneratorInterface $routeGenerator,
        private RouteRepositoryInterface $routeRepository,
        private RequestContext $requestContext
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        /** @var SettingsData $result */
        $result = [
            'availableLocales' => $dimensionContent->getAvailableLocales() ?? [],
        ];

        $references = [];

        // TODO handle properties filtering
        if ($dimensionContent instanceof RoutableInterface && $dimensionContent instanceof TemplateInterface) {
            $result = \array_merge($result, $this->getLocalizationsData($dimensionContent));
        }

        if ($dimensionContent instanceof WebspaceInterface) {
            $result = \array_merge($result, $this->getWebspaceData($dimensionContent));
        }

        if ($dimensionContent instanceof TemplateInterface) {
            $result = \array_merge($result, $this->getTemplateData($dimensionContent));
        }

        if ($dimensionContent instanceof AuthorInterface) {
            $result = \array_merge($result, $this->getAuthorData($dimensionContent));
        }

        if ($dimensionContent instanceof ShadowInterface) {
            $result = \array_merge($result, $this->getShadowData($dimensionContent));
        }

        return ContentView::createWithReferences($result, [], $references);
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param RoutableInterface&TemplateInterface&DimensionContentInterface<T> $dimensionContent
     *
     * @return array{
     *     localizations?: array<string, array{
     *         locale: string,
     *         url: string,
     *         alternate: bool
     *     }>
     * }
     */
    protected function getLocalizationsData(RoutableInterface&TemplateInterface&DimensionContentInterface $dimensionContent): array
    {
        $localizationData = [];

        $availableLocales = $dimensionContent->getAvailableLocales();

        if (null === $availableLocales) {
            return [];
        }

        $routes = $this->routeRepository->findBy([
            'locales' => $availableLocales,
            'resourceKey' => $dimensionContent->getResourceKey(),
            'resourceId' => (string) $dimensionContent->getResourceId(),
        ]);

        foreach ($routes as $route) {
            // TODO remove this hack, when we have a better way to determine the current site
            if (null === $this->requestContext->getParameter(RequestAttributeEnum::SITE->value)) {
                $this->requestContext->setParameter(RequestAttributeEnum::SITE->value, $route->getSite());
            }

            $locale = $route->getLocale();

            $resolvedUrl = $this->routeGenerator->generate(
                $route->getSlug(),
                $locale,
                $route->getSite(),
            );

            $localizationData[$locale] = [
                'locale' => $locale,
                'url' => $resolvedUrl,
                'alternate' => '' !== $resolvedUrl,
            ];
        }

        \ksort($localizationData);

        return [
            'localizations' => $localizationData,
        ];
    }

    /**
     * @return array{
     *     mainWebspace: string|null
     * }
     */
    protected function getWebspaceData(WebspaceInterface $dimensionContent): array
    {
        return [
            'mainWebspace' => $dimensionContent->getMainWebspace(),
        ];
    }

    /**
     * @return array{
     *     template: string|null
     * }
     */
    protected function getTemplateData(TemplateInterface $dimensionContent): array
    {
        return [
            'template' => $dimensionContent->getTemplateKey(),
        ];
    }

    /**
     * @return array{
     *     author: ContentView|null,
     *     authored: \DateTimeInterface|null
     * }
     */
    protected function getAuthorData(AuthorInterface $dimensionContent): array
    {
        $authorId = $dimensionContent->getAuthor()?->getId();
        $author = ContentView::createWithReferences(
            $authorId,
            [],
            $authorId ?
                [new Reference($authorId, UserInterface::RESOURCE_KEY)] :
                []
        );

        return [
            'author' => $author,
            'authored' => $dimensionContent->getAuthored(),
            'lastModified' => $dimensionContent->getLastModified(),
        ];
    }

    /**
     * @return array{
     *     shadowBaseLocale: string|null
     * }
     */
    protected function getShadowData(ShadowInterface $dimensionContent): array
    {
        return [
            'shadowBaseLocale' => $dimensionContent->getShadowLocale(),
        ];
    }
}
