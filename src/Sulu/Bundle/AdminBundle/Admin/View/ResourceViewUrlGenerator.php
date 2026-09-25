<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResourceViewUrlGenerator implements ResourceViewUrlGeneratorInterface
{
    /**
     * @param array<string, array{views?: array<string, string>}> $resources
     * @param iterable<ResourceViewParameterProviderInterface> $viewParameterProviders sorted by tag priority desc
     */
    public function __construct(
        private ViewUrlGeneratorInterface $viewUrlGenerator,
        private array $resources,
        private iterable $viewParameterProviders,
    ) {
    }

    public function generate(
        string $resourceKey,
        string $resourceView,
        array $viewParameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $viewName = $this->resources[$resourceKey]['views'][$resourceView] ?? null;

        if (null === $viewName) {
            throw new ResourceViewNotFoundException($resourceKey, $resourceView);
        }

        $providedViewParameters = [];
        foreach ($this->viewParameterProviders as $viewParameterProvider) {
            if ($viewParameterProvider::getResourceKey() !== $resourceKey) {
                continue;
            }

            $providedViewParameters += $viewParameterProvider->getViewParameters($resourceView, $viewParameters);
        }

        $viewParameters = \array_merge($providedViewParameters, $viewParameters);

        if (\str_contains($viewName, '{')) {
            $viewName = $this->resolveViewName($viewName, $viewParameters);
        }

        return $this->viewUrlGenerator->generate($viewName, $viewParameters, $referenceType);
    }

    /**
     * @param array<string, int|string> $viewParameters
     */
    private function resolveViewName(string $viewName, array $viewParameters): string
    {
        return (string) \preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\}/',
            function(array $matches) use ($viewName, $viewParameters) {
                $parameter = $matches[1];

                if (!\array_key_exists($parameter, $viewParameters)) {
                    throw new ViewParameterNotFoundException($parameter, $viewName);
                }

                return (string) $viewParameters[$parameter];
            },
            $viewName
        );
    }
}
