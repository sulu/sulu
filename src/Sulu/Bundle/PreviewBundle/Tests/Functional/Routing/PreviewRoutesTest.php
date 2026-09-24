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

namespace Sulu\Bundle\PreviewBundle\Tests\Functional\Routing;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * The routes that map form data carry `preview`, so the review lock lets them through.
 */
class PreviewRoutesTest extends SuluTestCase
{
    public function testMappingRoutesAreMarkedAsPreview(): void
    {
        $routes = $this->getRouter()->getRouteCollection();

        foreach (['sulu_preview.render', 'sulu_preview.update', 'sulu_preview.update-context'] as $name) {
            $this->assertTrue($routes->get($name)?->getDefault('preview'), $name);
        }
    }

    public function testOtherRoutesAreNotMarkedAsPreview(): void
    {
        $routes = $this->getRouter()->getRouteCollection();

        foreach (['sulu_preview.start', 'sulu_preview.stop', 'sulu_preview.public_render'] as $name) {
            $this->assertNotNull($routes->get($name), $name);
            $this->assertNull($routes->get($name)->getDefault('preview'), $name);
        }
    }

    private function getRouter(): RouterInterface
    {
        /** @var RouterInterface */
        return self::getContainer()->get('router');
    }
}
