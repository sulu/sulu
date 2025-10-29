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

namespace Sulu\CustomUrl\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;
use Sulu\CustomUrl\Domain\Model\CustomUrlRouteInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class CustomUrlRouteControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    /**
     * @var KernelBrowser
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );
    }

    /**
     * @param array<string> $domainParts
     */
    private function createCustomUrlWithRoutes(
        string $webspaceKey = 'sulu_io',
        array $domainParts = ['test', 'route'],
        int $historyRoutesCount = 3,
    ): CustomUrl {
        $customUrl = new CustomUrl(Uuid::v4()->toRfc4122());
        $customUrl->setTitle('Test Custom URL with Routes ' . \uniqid());
        $customUrl->setPublished(true);
        $customUrl->setBaseDomain('*.sulu.io/*');
        $customUrl->setDomainParts($domainParts);
        $customUrl->setTargetDocument(Uuid::v4()->toRfc4122());
        $customUrl->setTargetLocale('en');
        $customUrl->setCanonical(true);
        $customUrl->setRedirect(true);
        $customUrl->setNoFollow(false);
        $customUrl->setNoIndex(false);
        $customUrl->setWebspace($webspaceKey);

        $entityManager = self::getEntityManager();
        $entityManager->persist($customUrl);

        $uniqueId = \uniqid();

        // Create current route (not history)
        $currentRoute = new CustomUrlRoute($customUrl, \sprintf('%s.sulu.io/route-%s', $domainParts[0], $uniqueId));
        $currentRoute->setUuid(Uuid::v4()->toRfc4122());
        $currentRoute->setHistory(false);
        $entityManager->persist($currentRoute);

        // Create history routes
        $historyRouteIds = [];
        for ($i = 1; $i <= $historyRoutesCount; ++$i) {
            $historyRoute = new CustomUrlRoute($customUrl, \sprintf('%s.sulu.io/old-route-%d-%s', $domainParts[0], $i, $uniqueId));
            $historyRoute->setUuid(Uuid::v4()->toRfc4122());
            $historyRoute->setHistory(true);
            $entityManager->persist($historyRoute);
        }

        $entityManager->flush();

        return $customUrl;
    }

    /**
     * @return array{customUrlId: string, routeIds: array<string>}
     */
    public function testCgetAction(): array
    {
        self::purgeDatabase();

        $customUrl = $this->createCustomUrlWithRoutes('sulu_io', ['history', 'test'], 3);

        /** @var CustomUrlRouteRepositoryInterface $routeRepository */
        $routeRepository = self::getContainer()->get(CustomUrlRouteRepositoryInterface::class);
        $historyRoutes = $routeRepository->findBy(['customUrl' => $customUrl->getUuid(), 'history' => true]);

        $this->assertCount(3, $historyRoutes);

        $historyRoutesArray = \is_array($historyRoutes) ? $historyRoutes : \iterator_to_array($historyRoutes);
        $routeIds = \array_values(\array_filter(\array_map(fn (CustomUrlRouteInterface $route) => $route->getUuid(), $historyRoutesArray)));

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?page=1&limit=10&locale=en',
                $customUrl->getUuid(),
            ),
        );

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_route_cget.json', $response, Response::HTTP_OK);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('_embedded', $content);
        $this->assertIsArray($content['_embedded']);
        $this->assertArrayHasKey('custom_url_routes', $content['_embedded']);
        $this->assertIsArray($content['_embedded']['custom_url_routes']);
        $this->assertCount(3, $content['_embedded']['custom_url_routes']);

        return [
            'customUrlId' => $customUrl->getUuid(),
            'routeIds' => $routeIds,
        ];
    }

    /**
     * @param array{customUrlId: string, routeIds: array<string>} $testData
     */
    #[Depends('testCgetAction')]
    public function testCgetActionWithPagination(array $testData): string
    {
        $customUrlId = $testData['customUrlId'];

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?page=1&limit=2&locale=en',
                $customUrlId,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_route_cget_pagination.json', $response, Response::HTTP_OK);

        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('total', $content);
        $this->assertSame(3, $content['total']);
        $this->assertArrayHasKey('_embedded', $content);
        $this->assertIsArray($content['_embedded']);
        $this->assertArrayHasKey('custom_url_routes', $content['_embedded']);
        $this->assertIsArray($content['_embedded']['custom_url_routes']);
        $this->assertCount(2, $content['_embedded']['custom_url_routes']);

        return $customUrlId;
    }

    #[Depends('testCgetActionWithPagination')]
    public function testCgetActionNonExistentCustomUrl(string $customUrlId): string
    {
        $nonExistentId = Uuid::v4()->toRfc4122();

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?page=1&limit=10&locale=en',
                $nonExistentId,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NOT_FOUND, $response);

        return $customUrlId;
    }

    #[Depends('testCgetActionNonExistentCustomUrl')]
    public function testCgetActionWrongWebspace(string $customUrlId): string
    {
        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/admin/api/webspaces/wrong_webspace/custom-urls/%s/routes?page=1&limit=10&locale=en',
                $customUrlId,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NOT_FOUND, $response);

        return $customUrlId;
    }

    #[Depends('testCgetActionWrongWebspace')]
    public function testCgetActionOnlyHistoryRoutes(string $customUrlId): string
    {
        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?page=1&limit=10&locale=en',
                $customUrlId,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_OK, $response);

        /** @var array{_embedded: array{custom_url_routes: array<array{id: string, history: bool}>}} $content */
        $content = \json_decode((string) $response->getContent(), true);

        // Verify all returned routes are history routes
        foreach ($content['_embedded']['custom_url_routes'] as $route) {
            $this->assertTrue($route['history'], 'All routes should be history routes');
        }

        return $customUrlId;
    }

    public function testCdeleteAction(): string
    {
        self::purgeDatabase();

        $customUrl = $this->createCustomUrlWithRoutes('sulu_io', ['delete', 'test'], 5);

        /** @var CustomUrlRouteRepositoryInterface $routeRepository */
        $routeRepository = self::getContainer()->get(CustomUrlRouteRepositoryInterface::class);
        $historyRoutes = $routeRepository->findBy(['customUrl' => $customUrl->getUuid(), 'history' => true]);

        $this->assertCount(5, $historyRoutes);

        // Get first 3 route IDs to delete
        $historyRoutesArray = \is_array($historyRoutes) ? $historyRoutes : \iterator_to_array($historyRoutes);
        $routeIdsToDelete = \array_slice(\array_map(fn (CustomUrlRouteInterface $route) => $route->getUuid(), $historyRoutesArray), 0, 3);

        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?ids=%s',
                $customUrl->getUuid(),
                \implode(',', $routeIdsToDelete),
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NO_CONTENT, $response);

        // Verify routes were deleted
        $remainingRoutes = $routeRepository->findBy(['customUrl' => $customUrl->getUuid(), 'history' => true]);
        $this->assertCount(2, $remainingRoutes);

        return $customUrl->getUuid();
    }

    #[Depends('testCdeleteAction')]
    public function testCdeleteActionNonExistentCustomUrl(string $customUrlId): string
    {
        $nonExistentId = Uuid::v4()->toRfc4122();

        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?ids=%s',
                $nonExistentId,
                Uuid::v4()->toRfc4122(),
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NOT_FOUND, $response);

        return $customUrlId;
    }

    #[Depends('testCdeleteActionNonExistentCustomUrl')]
    public function testCdeleteActionWrongWebspace(string $customUrlId): string
    {
        /** @var CustomUrlRouteRepositoryInterface $routeRepository */
        $routeRepository = self::getContainer()->get(CustomUrlRouteRepositoryInterface::class);
        $historyRoutes = $routeRepository->findBy(['customUrl' => $customUrlId, 'history' => true]);

        $this->assertNotEmpty($historyRoutes);
        $historyRoutesArray = \is_array($historyRoutes) ? $historyRoutes : \iterator_to_array($historyRoutes);
        $firstRoute = \reset($historyRoutesArray);
        $this->assertInstanceOf(CustomUrlRouteInterface::class, $firstRoute);
        $routeId = $firstRoute->getUuid();

        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/admin/api/webspaces/wrong_webspace/custom-urls/%s/routes?ids=%s',
                $customUrlId,
                $routeId,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NOT_FOUND, $response);

        return $customUrlId;
    }

    #[Depends('testCdeleteActionWrongWebspace')]
    public function testCdeleteActionNonHistoryRoute(string $customUrlId): void
    {
        /** @var CustomUrlRepositoryInterface $customUrlRepository */
        $customUrlRepository = self::getContainer()->get(CustomUrlRepositoryInterface::class);
        $customUrl = $customUrlRepository->findOneBy(['uuid' => $customUrlId]);

        $this->assertNotNull($customUrl);

        /** @var CustomUrlRouteRepositoryInterface $routeRepository */
        $routeRepository = self::getContainer()->get(CustomUrlRouteRepositoryInterface::class);

        $allRoutes = $routeRepository->findBy(['customUrl' => $customUrlId]);
        $beforeCount = \is_countable($allRoutes) ? \count($allRoutes) : \count(\iterator_to_array($allRoutes));

        // Get current (non-history) route
        $currentRoute = $routeRepository->findOneBy(['customUrl' => $customUrlId, 'history' => false]);
        $this->assertNotNull($currentRoute);
        $currentRouteUuid = $currentRoute->getUuid();

        // Try to delete non-history route (should be ignored)
        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?ids=%s',
                $customUrlId,
                $currentRouteUuid,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NO_CONTENT, $response);

        // Verify current route was NOT deleted
        $allRoutesAfter = $routeRepository->findBy(['customUrl' => $customUrlId]);
        $afterCount = \is_countable($allRoutesAfter) ? \count($allRoutesAfter) : \count(\iterator_to_array($allRoutesAfter));
        $this->assertSame($beforeCount, $afterCount);

        if ($currentRouteUuid) {
            $currentRouteStillExists = $routeRepository->findOneBy(['uuid' => $currentRouteUuid]);
            $this->assertNotNull($currentRouteStillExists);
        }
    }

    public function testCdeleteActionEmptyIds(): void
    {
        self::purgeDatabase();

        $customUrl = $this->createCustomUrlWithRoutes('sulu_io', ['empty', 'ids'], 2);

        /** @var CustomUrlRouteRepositoryInterface $routeRepository */
        $routeRepository = self::getContainer()->get(CustomUrlRouteRepositoryInterface::class);
        $allRoutesBefore = $routeRepository->findBy(['customUrl' => $customUrl->getUuid()]);
        $beforeCount = \is_countable($allRoutesBefore) ? \count($allRoutesBefore) : \count(\iterator_to_array($allRoutesBefore));

        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?ids=',
                $customUrl->getUuid(),
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NO_CONTENT, $response);

        // Verify no routes were deleted
        $allRoutesAfter = $routeRepository->findBy(['customUrl' => $customUrl->getUuid()]);
        $afterCount = \is_countable($allRoutesAfter) ? \count($allRoutesAfter) : \count(\iterator_to_array($allRoutesAfter));
        $this->assertSame($beforeCount, $afterCount);
    }

    public function testCdeleteActionMultipleRoutes(): void
    {
        self::purgeDatabase();

        $customUrl = $this->createCustomUrlWithRoutes('sulu_io', ['multiple', 'delete'], 10);

        /** @var CustomUrlRouteRepositoryInterface $routeRepository */
        $routeRepository = self::getContainer()->get(CustomUrlRouteRepositoryInterface::class);
        $historyRoutes = $routeRepository->findBy(['customUrl' => $customUrl->getUuid(), 'history' => true]);

        $this->assertCount(10, $historyRoutes);
        $historyRoutesArray = \is_array($historyRoutes) ? $historyRoutes : \iterator_to_array($historyRoutes);

        // Delete all history routes
        $routeIdsToDelete = \array_map(fn (CustomUrlRouteInterface $route) => $route->getUuid(), $historyRoutesArray);

        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?ids=%s',
                $customUrl->getUuid(),
                \implode(',', $routeIdsToDelete),
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NO_CONTENT, $response);

        // Verify all history routes were deleted
        $remainingHistoryRoutes = $routeRepository->findBy(['customUrl' => $customUrl->getUuid(), 'history' => true]);
        $this->assertCount(0, $remainingHistoryRoutes);

        // Verify current route still exists
        $currentRoute = $routeRepository->findOneBy(['customUrl' => $customUrl->getUuid(), 'history' => false]);
        $this->assertNotNull($currentRoute);
    }

    public function testCdeleteActionPartiallyInvalidIds(): void
    {
        self::purgeDatabase();

        $customUrl = $this->createCustomUrlWithRoutes('sulu_io', ['partial', 'invalid'], 3);

        /** @var CustomUrlRouteRepositoryInterface $routeRepository */
        $routeRepository = self::getContainer()->get(CustomUrlRouteRepositoryInterface::class);
        $historyRoutes = $routeRepository->findBy(['customUrl' => $customUrl->getUuid(), 'history' => true]);

        $historyRoutesArray = \is_array($historyRoutes) ? $historyRoutes : \iterator_to_array($historyRoutes);
        $firstRoute = \reset($historyRoutesArray);
        $this->assertInstanceOf(CustomUrlRouteInterface::class, $firstRoute);
        $validRouteId = $firstRoute->getUuid();
        $invalidRouteId = Uuid::v4()->toRfc4122();

        // Mix valid and invalid route IDs
        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?ids=%s,%s',
                $customUrl->getUuid(),
                $validRouteId,
                $invalidRouteId,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NO_CONTENT, $response);

        // Verify valid route was deleted
        if ($validRouteId) {
            $deletedRoute = $routeRepository->findOneBy(['uuid' => $validRouteId]);
            $this->assertNull($deletedRoute);
        }

        // Verify remaining history routes still exist
        $remainingHistoryRoutes = $routeRepository->findBy(['customUrl' => $customUrl->getUuid(), 'history' => true]);
        $this->assertCount(2, $remainingHistoryRoutes);
    }

    public function testCdeleteActionRouteFromDifferentCustomUrl(): void
    {
        self::purgeDatabase();

        $customUrl1 = $this->createCustomUrlWithRoutes('sulu_io', ['custom', 'url1'], 2);
        $customUrl2 = $this->createCustomUrlWithRoutes('sulu_io', ['custom', 'url2'], 2);

        /** @var CustomUrlRouteRepositoryInterface $routeRepository */
        $routeRepository = self::getContainer()->get(CustomUrlRouteRepositoryInterface::class);

        $customUrl1Routes = $routeRepository->findBy(['customUrl' => $customUrl1->getUuid(), 'history' => true]);
        $customUrl2Routes = $routeRepository->findBy(['customUrl' => $customUrl2->getUuid(), 'history' => true]);

        $this->assertCount(2, $customUrl1Routes);
        $this->assertCount(2, $customUrl2Routes);

        // Try to delete route from customUrl2 using customUrl1's endpoint
        $customUrl2RoutesArray = \is_array($customUrl2Routes) ? $customUrl2Routes : \iterator_to_array($customUrl2Routes);
        $firstRouteFromCustomUrl2 = \reset($customUrl2RoutesArray);
        $this->assertInstanceOf(CustomUrlRouteInterface::class, $firstRouteFromCustomUrl2);
        $routeFromCustomUrl2 = $firstRouteFromCustomUrl2->getUuid();

        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/admin/api/webspaces/sulu_io/custom-urls/%s/routes.json?ids=%s',
                $customUrl1->getUuid(),
                $routeFromCustomUrl2,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NO_CONTENT, $response);

        // Verify route from customUrl2 was NOT deleted (belongs to different custom URL)
        if ($routeFromCustomUrl2) {
            $routeStillExists = $routeRepository->findOneBy(['uuid' => $routeFromCustomUrl2]);
            $this->assertNotNull($routeStillExists);
        }

        // Verify both custom URLs still have all their routes
        $this->assertCount(2, $routeRepository->findBy(['customUrl' => $customUrl1->getUuid(), 'history' => true]));
        $this->assertCount(2, $routeRepository->findBy(['customUrl' => $customUrl2->getUuid(), 'history' => true]));
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}
