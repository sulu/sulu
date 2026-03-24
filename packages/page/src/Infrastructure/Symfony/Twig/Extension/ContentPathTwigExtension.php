<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Symfony\Twig\Extension;

use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal This class is internal and should not be extended or overwritten.
 *           You can create an own Twig Extension to override the behaviour.
 */
final class ContentPathTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly RouteGeneratorInterface $routeGenerator,
        private readonly UrlMatcherInterface $urlMatcher,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_content_path', [$this, 'suluContentPath']),
            new TwigFunction('sulu_content_root_path', [$this, 'suluContentRootPath']),
        ];
    }

    public function suluContentRootPath(): string
    {
        return $this->suluContentPath('/');
    }

    public function suluContentPath(string $slug, ?string $webspaceKey = null, ?string $locale = null): string
    {
        if (!\str_starts_with($slug, '/')) {
            return $slug;
        }

        try {
            $this->urlMatcher->match($slug);

            return $slug;
        } catch (ResourceNotFoundException) {
            return $this->routeGenerator->generate(
                $slug,
                $locale,
                $webspaceKey,
            );
        }
    }
}
