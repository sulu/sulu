<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;

class SnippetResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<SnippetRepositoryInterface>
     */
    private ObjectProphecy $snippetRepository;

    private SnippetResourceLoader $loader;

    public function setUp(): void
    {
        $this->snippetRepository = $this->prophesize(SnippetRepositoryInterface::class);
        $this->loader = new SnippetResourceLoader($this->snippetRepository->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('snippet', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $snippet1 = $this->createSnippet('1');
        $snippet2 = $this->createSnippet('3');

        $this->snippetRepository->findBy(
            [
                'uuids' => ['1', '3'],
                'locale' => 'en',
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            [],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true]
        )->willReturn([
            $snippet1,
            $snippet2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load(['1', '3'], 'en');

        $this->assertSame([
            '1' => $snippet1,
            '3' => $snippet2,
        ], $result);
    }

    public function testLoadWithShadowLocaleFallback(): void
    {
        $snippet1 = $this->createSnippet('1');

        // First query: de_li finds snippet 1 but not snippet 2.
        $this->snippetRepository->findBy(
            [
                'uuids' => ['1', '2'],
                'locale' => 'de_li',
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            [],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true]
        )->willReturn([$snippet1])
            ->shouldBeCalled();

        $snippet2 = $this->createSnippet('2');

        // Second query: shadow base locale de finds snippet 2.
        $this->snippetRepository->findBy(
            [
                'uuids' => ['2'],
                'locale' => 'de',
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            [],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true]
        )->willReturn([$snippet2])
            ->shouldBeCalled();

        $result = $this->loader->load(['1', '2'], 'de_li', ['_shadowLocale' => 'de']);

        $this->assertSame([
            '1' => $snippet1,
            '2' => $snippet2,
        ], $result);
    }

    public function testLoadWithShadowLocaleNotTriggeredWhenAllFound(): void
    {
        $snippet1 = $this->createSnippet('1');
        $snippet2 = $this->createSnippet('2');

        // All snippets found in page locale — no fallback query.
        $this->snippetRepository->findBy(
            [
                'uuids' => ['1', '2'],
                'locale' => 'de_li',
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            [],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true]
        )->willReturn([$snippet1, $snippet2])
            ->shouldBeCalled();

        $result = $this->loader->load(['1', '2'], 'de_li', ['_shadowLocale' => 'de']);

        $this->assertSame([
            '1' => $snippet1,
            '2' => $snippet2,
        ], $result);
    }

    public function testLoadWithoutShadowLocaleNoFallback(): void
    {
        $snippet1 = $this->createSnippet('1');

        // No _shadowLocale in params — no fallback for missing snippet 2.
        $this->snippetRepository->findBy(
            [
                'uuids' => ['1', '2'],
                'locale' => 'de_li',
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            [],
            [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true]
        )->willReturn([$snippet1])
            ->shouldBeCalled();

        $result = $this->loader->load(['1', '2'], 'de_li');

        $this->assertSame([
            '1' => $snippet1,
        ], $result);
    }

    private static function createSnippet(string $uuid): Snippet
    {
        return new Snippet($uuid);
    }
}
