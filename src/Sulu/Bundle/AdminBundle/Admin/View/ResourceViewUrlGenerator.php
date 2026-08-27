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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResourceViewUrlGenerator implements ResourceViewUrlGeneratorInterface
{
    /**
     * @param array<string, array{views?: array<string, string>}> $resources
     */
    public function __construct(
        private ViewUrlGeneratorInterface $viewUrlGenerator,
        private array $resources,
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

        return $this->viewUrlGenerator->generate($viewName, $viewParameters, $referenceType);
    }
}
