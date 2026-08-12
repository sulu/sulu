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

use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ViewUrlGenerator implements ViewUrlGeneratorInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ViewRegistry $viewRegistry,
        private ?RequestStack $requestStack = null,
    ) {
    }

    public function generate(
        string $viewName,
        array $viewParameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        $view = $this->viewRegistry->findViewByName($viewName);
        $viewParameters = $this->resolveDefaultParameters($view->getPath(), $viewParameters);

        $path = \preg_replace_callback(
            '/:([a-zA-Z0-9_]+)/',
            function(array $matches) use ($viewName, $viewParameters) {
                $parameter = $matches[1];

                if (!\array_key_exists($parameter, $viewParameters)) {
                    throw new ViewParameterNotFoundException($parameter, $viewName);
                }

                return \rawurlencode((string) $viewParameters[$parameter]);
            },
            $view->getPath()
        );

        $adminUrl = $this->urlGenerator->generate('sulu_admin', [], $referenceType);

        return \rtrim($adminUrl, '/') . '/#' . $path;
    }

    /**
     * @param array<string, int|string> $viewParameters
     *
     * @return array<string, int|string>
     */
    private function resolveDefaultParameters(string $path, array $viewParameters): array
    {
        $request = $this->requestStack?->getCurrentRequest();

        if (!$request) {
            return $viewParameters;
        }

        $webspace = $request->attributes->get('webspace');

        if (!\array_key_exists('webspace', $viewParameters)
            && \str_contains($path, ':webspace')
            && \is_string($webspace)
            && '' !== $webspace
        ) {
            $viewParameters['webspace'] = $webspace;
        }

        if (!\array_key_exists('locale', $viewParameters) && \str_contains($path, ':locale')) {
            $locale = $request->query->get('locale');
            $viewParameters['locale'] = \is_string($locale) && '' !== $locale ? $locale : $request->getLocale();
        }

        return $viewParameters;
    }
}
