<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Sulu\Content\ResourceLoader\ArticleResourceLoader;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;

class ArticleResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<ArticleRepositoryInterface>
     */
    private ObjectProphecy $articleRepository;

    private ArticleResourceLoader $loader;

    public function setUp(): void
    {
        $this->articleRepository = $this->prophesize(ArticleRepositoryInterface::class);
        $this->loader = new ArticleResourceLoader($this->articleRepository->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('article', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $article1 = $this->createArticle('1');
        $article2 = $this->createArticle('3');

        $this->articleRepository->findBy(['uuids' => ['1', '3']])->willReturn([
            $article1,
            $article2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load(['1', '3'], 'en');

        $this->assertSame([
            '1' => $article1,
            '3' => $article2,
        ], $result);
    }

    private static function createArticle(string $uuid): Article
    {
        return new Article($uuid);
    }
}
