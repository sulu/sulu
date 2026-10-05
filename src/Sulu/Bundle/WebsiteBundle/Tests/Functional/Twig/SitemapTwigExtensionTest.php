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

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Twig\Environment;
use Twig\TwigFunction;

class SitemapTwigExtensionTest extends WebsiteTestCase
{
    protected function setUp(): void
    {
        self::createWebsiteClient();
    }

    /**
     * @return iterable<array{0: string}>
     */
    public static function provideFunctionName(): iterable
    {
        yield ['sulu_sitemap'];
        yield ['sulu_sitemap_url'];
        yield ['sulu_sitemap_aliases'];
    }

    #[DataProvider('provideFunctionName')]
    public function testFunctionIsRegistered(string $functionName): void
    {
        $this->assertInstanceOf(TwigFunction::class, $this->getTwig()->getFunction($functionName));
    }

    public function testSitemapAliasesContainsTaggedProviders(): void
    {
        $aliases = $this->getTwig()->createTemplate('{{ sulu_sitemap_aliases()|join(",") }}')->render();

        $this->assertContains('test', \explode(',', $aliases));
    }

    public function testSitemapWithoutRequestReturnsEmptyList(): void
    {
        $this->assertSame('0', $this->getTwig()->createTemplate('{{ sulu_sitemap()|length }}')->render());
    }

    private function getTwig(): Environment
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        return $twig;
    }
}
