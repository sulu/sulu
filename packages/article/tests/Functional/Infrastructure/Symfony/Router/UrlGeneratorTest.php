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

namespace Sulu\Article\Tests\Functional\Infrastructure\Symfony\Router;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestWith;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;

#[CoversNothing]
class UrlGeneratorTest extends KernelTestCase
{
    /**
     * @var array<string, string>
     */
    private static array $testedRoutes = [];

    /**
     * @param array<string, mixed> $params
     */
    #[TestWith(['sulu_article.get_articles', [], '/admin/api/articles'])]
    #[TestWith(['sulu_article.get_article', ['id' => '019905eb-ae9a-7136-93f2-06557330e3ad'], '/admin/api/articles/019905eb-ae9a-7136-93f2-06557330e3ad'])]
    #[TestWith(['sulu_article.post_article', [], '/admin/api/articles'])]
    #[TestWith(['sulu_article.put_article', ['id' => '019905eb-ae9a-7136-93f2-06557330e3ad'], '/admin/api/articles/019905eb-ae9a-7136-93f2-06557330e3ad'])]
    #[TestWith(['sulu_article.delete_article', ['id' => '019905eb-ae9a-7136-93f2-06557330e3ad'], '/admin/api/articles/019905eb-ae9a-7136-93f2-06557330e3ad'])]
    #[TestWith(['sulu_article.post_article_trigger', ['id' => '019905eb-ae9a-7136-93f2-06557330e3ad'], '/admin/api/articles/019905eb-ae9a-7136-93f2-06557330e3ad'])]
    #[TestWith(['sulu_article.get_article_versions', ['id' => '019905eb-ae9a-7136-93f2-06557330e3ad'], '/admin/api/articles/019905eb-ae9a-7136-93f2-06557330e3ad/versions'])]
    public function testRoutes(string $route, array $params, string $expectedUrl): void
    {
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);

        self::$testedRoutes[$route] = $route;

        $this->assertSame(
            $expectedUrl,
            $urlGenerator->generate($route, $params)
        );
    }

    #[Depends('testRoutes')]
    public function testAllRoutesTested(): void
    {
        $routes = Yaml::parse(\file_get_contents(__DIR__ . '/../../../../../config/routing_admin_api.yaml') ?: '') ?? [];
        $this->assertIsArray($routes);
        $this->assertGreaterThan(0, \count($routes), 'No routes found in routing_admin_api.yaml');

        $this->assertSame(
            \array_keys($routes),
            \array_keys(self::$testedRoutes),
        );
    }

    public static function teardownAfterClass(): void
    {
        self::$testedRoutes = [];
        parent::teardownAfterClass();
    }
}
