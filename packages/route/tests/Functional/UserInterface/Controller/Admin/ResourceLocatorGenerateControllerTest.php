<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\UserInterface\Controller\Admin;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Userinterface\Controller\Admin\ResourceLocatorGenerateController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversClass(ResourceLocatorGenerateController::class)]
class ResourceLocatorGenerateControllerTest extends SuluTestCase
{
    use PHPMatcherAssertions;

    private KernelBrowser $client;

    public static function setUpBeforeClass(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        $homepageEn = new Route('pages', '1', 'en', '/', 'website');
        $entityManager->persist($homepageEn);
        $homepageDe = new Route('pages', '1', 'de', '/', 'website');
        $entityManager->persist($homepageDe);
        $homepageFr = new Route('pages', '1', 'fr', $homepageEn->getSlug(), 'website');
        $entityManager->persist($homepageFr);

        $parentEn = new Route('pages', '2', 'en', '/parent', 'website', $homepageEn);
        $entityManager->persist($parentEn);
        $parentDe = new Route('pages', '2', 'de', '/eltern', 'website', $homepageDe);
        $entityManager->persist($parentDe);
        $parentFr = new Route('pages', '2', 'fr', $parentEn->getSlug(), 'website', $homepageFr);
        $entityManager->persist($parentFr);

        $childAEn = new Route('pages', '3', 'en', '/parent/child-a', 'website', $parentEn);
        $entityManager->persist($childAEn);
        $childADe = new Route('pages', '3', 'de', '/eltern/kind-a', 'website', $parentDe);
        $entityManager->persist($childADe);
        $childAFr = new Route('pages', '3', 'fr', $childAEn->getSlug(), 'website', $parentFr);
        $entityManager->persist($childAFr);

        $childBEn = new Route('pages', '4', 'en', '/parent/child-b', 'website', $parentEn);
        $entityManager->persist($childBEn);
        $childBDe = new Route('pages', '4', 'de', '/eltern/kind-b', 'website', $parentDe);
        $entityManager->persist($childBDe);
        $childBFr = new Route('pages', '4', 'fr', $childBEn->getSlug(), 'website', $parentFr);
        $entityManager->persist($childBFr);

        $otherHomepageEn = new Route('pages', '5', 'en', '/', 'intranet');
        $entityManager->persist($otherHomepageEn);
        $homepageDe = new Route('pages', '5', 'de', '/', 'intranet');
        $entityManager->persist($homepageDe);

        $parentEn = new Route('pages', '6', 'en', '/parent', 'intranet', $homepageEn);
        $entityManager->persist($parentEn);
        $parentDe = new Route('pages', '6', 'de', '/eltern', 'intranet', $homepageDe);
        $entityManager->persist($parentDe);

        $historyRoute1 = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::2', 'en', '/test', 'website');
        $entityManager->persist($historyRoute1);
        $historyRoute2 = new Route(Route::HISTORY_RESOURCE_KEY, 'pages::3', 'en', '/test/child-a', 'website');
        $entityManager->persist($historyRoute2);

        $entityManager->flush();
        $entityManager->clear();

        self::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement('DELETE FROM ro_next_routes WHERE 1 = 1');

        self::ensureKernelShutdown();
    }

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testGenerateNewArticle(): void
    {
        $this->client->request('POST', '/admin/api/resource-locators', content: \json_encode([
            'parts' => ['title' => 'Hello World'],
            'locale' => 'en',
            'webspace' => 'website',
            'resourceKey' => 'articles',
            'resourceId' => null,
        ], \JSON_THROW_ON_ERROR));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertMatchesPattern(<<<'JSON'
            {
                "resourceLocator": "/hello-world"
            }
        JSON, $response->getContent() ?: '');
    }

    public function testGenerateNewPageWithParent(): void
    {
        $this->client->request('POST', '/admin/api/resource-locators', content: \json_encode([
            'parts' => ['title' => 'Hello World'],
            'locale' => 'en',
            'webspace' => 'website',
            'resourceKey' => 'pages',
            'resourceId' => null,
            'parentId' => '2',
        ], \JSON_THROW_ON_ERROR));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertMatchesPattern(<<<'JSON'
            {
                "resourceLocator": "/parent/hello-world"
            }
        JSON, $response->getContent() ?: '');
    }

    public function testGenerateNewPageWithConflict(): void
    {
        $this->client->request('POST', '/admin/api/resource-locators', content: \json_encode([
            'parts' => ['title' => 'test'],
            'locale' => 'en',
            'webspace' => 'website',
            'resourceKey' => 'pages',
            'resourceId' => null,
        ], \JSON_THROW_ON_ERROR));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertMatchesPattern(<<<'JSON'
            {
                "resourceLocator": "/test-1"
            }
        JSON, $response->getContent() ?: '');
    }

    public function testGenerateNewPageWithHistoryConflict(): void
    {
        $this->client->request('POST', '/admin/api/resource-locators', content: \json_encode([
            'parts' => ['title' => 'Parent'],
            'locale' => 'en',
            'webspace' => 'website',
            'resourceKey' => 'pages',
            'resourceId' => null,
        ], \JSON_THROW_ON_ERROR));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertMatchesPattern(<<<'JSON'
            {
                "resourceLocator": "/parent-1"
            }
        JSON, $response->getContent() ?: '');
    }

    public function testGenerateNewPageNoConflictOtherSide(): void
    {
        $this->client->request('POST', '/admin/api/resource-locators', content: \json_encode([
            'parts' => ['title' => 'Child A'],
            'locale' => 'en',
            'webspace' => 'intranet',
            'resourceKey' => 'pages',
            'resourceId' => null,
            'parentId' => '6',
        ], \JSON_THROW_ON_ERROR));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertMatchesPattern(<<<'JSON'
            {
                "resourceLocator": "/parent/child-a"
            }
        JSON, $response->getContent() ?: '');
    }

    public function testGenerateExistPage(): void
    {
        $this->client->request('POST', '/admin/api/resource-locators', content: \json_encode([
            'parts' => ['title' => 'Parent'],
            'locale' => 'en',
            'webspace' => 'website',
            'resourceKey' => 'pages',
            'resourceId' => '2',
            'parentId' => '1',
        ], \JSON_THROW_ON_ERROR));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertMatchesPattern(<<<'JSON'
            {
                "resourceLocator": "/parent"
            }
        JSON, $response->getContent() ?: '');
    }

    public function testGenerateExistPageOtherResourceId(): void
    {
        $this->client->request('POST', '/admin/api/resource-locators', content: \json_encode([
            'parts' => ['title' => 'Parent'],
            'locale' => 'en',
            'webspace' => 'website',
            'resourceKey' => 'pages',
            'resourceId' => '3',
            'parentId' => '1',
        ], \JSON_THROW_ON_ERROR));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertMatchesPattern(<<<'JSON'
            {
                "resourceLocator": "/parent-1"
            }
        JSON, $response->getContent() ?: '');
    }
}
