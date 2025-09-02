<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal This class is internal and should not be extended or overwritten.
 *           You can create a separate admin class and override the config.
 */
final class RouteAdmin extends Admin
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getConfigKey(): string
    {
        return 'sulu_route';
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [
            'generateUrl' => $this->urlGenerator->generate('sulu_route.post_resource_locator'),
        ];
    }
}
