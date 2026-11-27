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

namespace Sulu\Article\Tests\Unit\Infrastructure\Symfony\HttpKernel;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Article\Infrastructure\Symfony\HttpKernel\SuluArticleBundle;

class SuluArticleBundleTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        $extension = (new SuluArticleBundle())->getContainerExtension();
        $this->assertNotNull($extension);

        return [$extension];
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function smartContentProviderProvider(): iterable
    {
        yield 'article' => ['sulu_article.article_smart_content_provider'];
        yield 'page tree article' => ['sulu_article.page_tree_article_smart_content_provider'];
    }

    #[DataProvider('smartContentProviderProvider')]
    public function testSmartContentProviderGetsConfiguredDefaultLimit(string $serviceId): void
    {
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.environment', 'test');

        $this->load(['smart_content' => ['default_limit' => 7]]);

        $defaultLimit = $this->container->getDefinition($serviceId)->getArgument(4);
        $this->assertSame(7, $this->container->getParameterBag()->resolveValue($defaultLimit));
    }
}
