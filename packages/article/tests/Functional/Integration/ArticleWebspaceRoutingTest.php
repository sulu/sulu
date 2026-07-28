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

namespace Sulu\Article\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Route\Domain\Value\RequestAttributeEnum;

#[CoversNothing]
class ArticleWebspaceRoutingTest extends SuluTestCase
{
    use CreateArticleTrait;

    protected function setUp(): void
    {
        self::bootKernel();

        $requestContext = self::getContainer()->get('router.request_context');
        $requestContext->setParameter(RequestAttributeEnum::WEBSPACE->value, 'sulu-io');
    }

    public function testArticleIsAvailableThroughMainWebspace(): void
    {
        self::purgeDatabase();

        self::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Webspace Routing Article',
                    'url' => '/webspace-routing-article',
                    'mainWebspace' => 'sulu-io',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', 'http://sulu.io/en/webspace-routing-article');

        $this->assertHttpStatusCode(200, $websiteClient->getResponse());
    }

    public function testArticleIsNotAvailableThroughUnrelatedWebspace(): void
    {
        self::purgeDatabase();

        // The article is only exposed through its main webspace "sulu-io". Its route is stored without a
        // webspace, so it technically matches every webspace slug - the ArticleRouteDefaultsProvider is
        // responsible for restricting it to the configured webspaces.
        self::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Webspace Routing Article',
                    'url' => '/webspace-routing-article',
                    'mainWebspace' => 'sulu-io',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', 'http://blog.io/en/webspace-routing-article');

        $this->assertHttpStatusCode(404, $websiteClient->getResponse());
    }
}
