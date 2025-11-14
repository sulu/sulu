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

namespace Sulu\Content\Tests\Functional\Infrastructure\Doctrine\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

class RouteCleanupListenerTest extends SuluTestCase
{
    use CreateExampleTrait;

    private EntityManagerInterface $entityManager;
    private RouteRepositoryInterface $routeRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();

        $this->entityManager = self::getEntityManager();
        $this->routeRepository = self::getContainer()->get('sulu_route.route_repository');
    }

    public function testDeleteDimensionContentKeepsRoutesWhenOtherDimensionsExist(): void
    {
        $example = self::createExample([
            'en' => [
                'draft' => ['title' => 'Draft', 'url' => '/draft'],
                'live' => ['title' => 'Live', 'url' => '/live'],
            ],
        ], ['create_route' => true]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $example = $this->entityManager->find(Example::class, $example->getId());
        $this->assertInstanceOf(Example::class, $example);

        $draftDimension = null;
        foreach ($example->getDimensionContents() as $dimensionContent) {
            if (DimensionContentInterface::STAGE_DRAFT === $dimensionContent->getStage()
                && 'en' === $dimensionContent->getLocale()
            ) {
                $draftDimension = $dimensionContent;
                break;
            }
        }

        $this->assertInstanceOf(ExampleDimensionContent::class, $draftDimension);
        $this->entityManager->remove($draftDimension);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $routes = $this->routeRepository->findBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
        ]);
        $this->assertCount(1, $routes);
    }

    public function testDeleteLastDimensionContentForLocaleDeletesRoutes(): void
    {
        $example = self::createExample([
            'en' => [
                'live' => ['title' => 'English', 'url' => '/english'],
            ],
        ], ['create_route' => true]);

        $this->entityManager->flush();
        $exampleId = $example->getId();
        $this->entityManager->clear();

        $routes = $this->routeRepository->findBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $exampleId,
            'locale' => 'en',
        ]);
        $this->assertCount(1, $routes);

        $example = $this->entityManager->find(Example::class, $exampleId);
        $this->assertInstanceOf(Example::class, $example);

        $englishDimensions = [];
        foreach ($example->getDimensionContents() as $dimensionContent) {
            if ('en' === $dimensionContent->getLocale()) {
                $englishDimensions[] = $dimensionContent;
            }
        }

        $this->assertNotEmpty($englishDimensions);
        foreach ($englishDimensions as $dimension) {
            $this->entityManager->remove($dimension);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $routes = $this->routeRepository->findBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $exampleId,
            'locale' => 'en',
        ]);
        $this->assertCount(0, $routes);
    }

    public function testDeleteContentRichEntityDeletesAllRoutes(): void
    {
        $example = self::createExample([
            'en' => [
                'live' => ['title' => 'English', 'url' => '/english'],
            ],
            'de' => [
                'live' => ['title' => 'German', 'url' => '/german'],
            ],
        ], ['create_route' => true]);

        $this->entityManager->flush();
        $exampleId = $example->getId();
        $this->entityManager->clear();

        $example = $this->entityManager->find(Example::class, $exampleId);
        $this->assertInstanceOf(Example::class, $example);
        $this->entityManager->remove($example);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $allRoutes = $this->routeRepository->findBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $exampleId,
        ]);
        $this->assertCount(0, $allRoutes);
    }

    public function testHistoryRoutesDeletedWithMainRoutes(): void
    {
        $example = self::createExample([
            'en' => [
                'live' => ['title' => 'Test Example', 'url' => '/test-example'],
            ],
        ], ['create_route' => true]);

        $this->entityManager->flush();
        $exampleId = $example->getId();

        $historyRoute = new Route(
            Route::HISTORY_RESOURCE_KEY,
            Example::RESOURCE_KEY . '::' . $exampleId,
            'en',
            '/old-url'
        );
        $this->entityManager->persist($historyRoute);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $example = $this->entityManager->find(Example::class, $exampleId);
        $this->assertInstanceOf(Example::class, $example);
        $this->entityManager->remove($example);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $mainRoutes = $this->routeRepository->findBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $exampleId,
        ]);
        $this->assertCount(0, $mainRoutes);

        $historyRoutes = $this->routeRepository->findBy([
            'resourceKey' => Route::HISTORY_RESOURCE_KEY,
            'resourceId' => Example::RESOURCE_KEY . '::' . $exampleId,
        ]);
        $this->assertCount(0, $historyRoutes);
    }

    public function testMultipleLocalesIndependentCleanup(): void
    {
        $example = self::createExample([
            'en' => [
                'live' => ['title' => 'English', 'url' => '/english'],
            ],
            'de' => [
                'live' => ['title' => 'German', 'url' => '/german'],
            ],
        ], ['create_route' => true]);

        $this->entityManager->flush();
        $exampleId = $example->getId();
        $this->entityManager->clear();

        $example = $this->entityManager->find(Example::class, $exampleId);
        $this->assertInstanceOf(Example::class, $example);

        $germanDimensions = [];
        foreach ($example->getDimensionContents() as $dimensionContent) {
            if ('de' === $dimensionContent->getLocale()) {
                $germanDimensions[] = $dimensionContent;
            }
        }

        $this->assertNotEmpty($germanDimensions);
        foreach ($germanDimensions as $dimension) {
            $this->entityManager->remove($dimension);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $deRoutes = $this->routeRepository->findBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $exampleId,
            'locale' => 'de',
        ]);
        $this->assertCount(0, $deRoutes);

        $enRoutes = $this->routeRepository->findBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $exampleId,
            'locale' => 'en',
        ]);
        $this->assertCount(1, $enRoutes);
    }
}
