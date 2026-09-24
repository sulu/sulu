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

namespace Sulu\Content\Tests\Functional\Application\ContentWorkflow;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentWorkflow\Subscriber\ContentReviewLockSubscriber;
use Symfony\Component\Routing\RouterInterface;

/**
 * The review lock lets the preview through by route name, so a renamed preview route would lock the
 * preview again without any other test noticing.
 */
#[CoversNothing]
class ContentReviewLockPreviewRoutesTest extends SuluTestCase
{
    public function testPreviewRoutesExist(): void
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get('router');
        $routes = $router->getRouteCollection();

        foreach (ContentReviewLockSubscriber::PREVIEW_ROUTES as $routeName) {
            $this->assertNotNull($routes->get($routeName), \sprintf('The preview route "%s" does not exist.', $routeName));
        }
    }
}
