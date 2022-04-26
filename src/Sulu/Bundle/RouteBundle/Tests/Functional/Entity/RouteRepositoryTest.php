<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Functional\Controller;

use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class RouteRepositoryTest extends SuluTestCase
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var int
     */
    private $routeId;

    public function setUp(): void
    {
        $this->purgeDatabase();

        $entityManager = $this->getEntityManager();

        /** @var RouteRepositoryInterface $repository */
        $repository = $entityManager->getRepository(Route::class);

        // create route
        $route = $repository->createNew()
            ->setEntityClass(\stdClass::class)
            ->setEntityId('123-123-123')
            ->setLocale('de')
            ->setPath('/test');

        $entityManager->persist($route);
        $entityManager->flush();
        $entityManager->clear();

        $this->route = $route;
        $this->routeId = $route->getId();
    }

    public function testRemove(): void
    {
        $entityManager = $this->getEntityManager();

        /** @var RouteRepositoryInterface $repository */
        $repository = $entityManager->getRepository(Route::class);

        // remove route
        $route = $entityManager->find(Route::class, $this->routeId);
        $repository->remove($route);
        $entityManager->flush();
        $entityManager->clear();

        // check for existence
        $route = $entityManager->find(Route::class, $this->routeId);
        $this->assertNull($route);
    }
}
