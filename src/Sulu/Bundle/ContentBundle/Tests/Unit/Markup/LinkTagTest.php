<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Markup;

use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkItem;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\ContentBundle\Markup\LinkTag;

class LinkTagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LinkProviderInterface[]
     */
    private $providers;

    /**
     * @var LinkProviderPoolInterface
     */
    private $providerPool;

    /**
     * @var LinkTag
     */
    private $linkTag;

    protected function setUp()
    {
        $providers = $this->providers = [
            'page' => $this->prophesize(LinkProviderInterface::class),
            'article' => $this->prophesize(LinkProviderInterface::class),
        ];
        $this->providerPool = $this->prophesize(LinkProviderPoolInterface::class);
        $this->providerPool->getProvider(Argument::any())->will(
            function ($arguments) use ($providers) {
                return $providers[$arguments[0]];
            }
        );

        $this->linkTag = new LinkTag($this->providerPool->reveal());
    }

    public function provideParseData()
    {
        return [
            [
                '<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="/de/test" title="Test-Title">Test-Content</a>',
            ],
            [
                '<sulu:link href="123-123-123" title="Test-Title" provider="article"/>',
                ['href' => '123-123-123', 'title' => 'Test-Title', 'provider' => 'article'],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="/de/test" title="Test-Title">Page-Title</a>',
            ],
            [
                '<sulu:link href="123-123-123" title="Test-Title" provider="article"></sulu:link>',
                ['href' => '123-123-123', 'title' => 'Test-Title', 'provider' => 'article'],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="/de/test" title="Test-Title">Page-Title</a>',
            ],
            [
                '<sulu:link href="123-123-123" provider="article">Test-Content</sulu:link>',
                ['href' => '123-123-123', 'content' => 'Test-Content', 'provider' => 'article'],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="/de/test" title="Page-Title">Test-Content</a>',
            ],
            [
                '<sulu:link href="123-123-123" title="Test-Title" target="_blank" provider="article">Test-Content</sulu:link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="/de/test" title="Test-Title" target="_blank">Test-Content</a>',
            ],
            [
                '<sulu:link href="123-123-123" title="Test-Title" target="_self" provider="article">Test-Content</sulu:link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_self',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="/de/test" title="Test-Title" target="_self">Test-Content</a>',
            ],
        ];
    }

    /**
     * @dataProvider provideParseData
     */
    public function testParseAll($tag, $attributes, $items, $expected)
    {
        $hrefs = [$attributes['href']];
        $this->providers[$attributes['provider']]->preload($hrefs, 'de', true)->willReturn($items);

        $result = $this->linkTag->parseAll([$tag => $attributes], 'de');

        $this->assertEquals([$tag => $expected], $result);
    }

    public function testParseAllMultipleTags()
    {
        $this->providers['article']->preload(['123-123-123', '312-312-312'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 1', '/de/test-1', true),
                    new LinkItem('312-312-312', 'Page-Title 2', '/de/test-2', true),
                ]
            );

        $tag1 = '<sulu:link href="123-123-123" provider="article">Test-Content</sulu:link>';
        $tag2 = '<sulu:link href="123-123-123" title="Test-Title" provider="article"/>';
        $tag3 = '<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>';
        $tag4 = '<sulu:link href="123-123-123" title="Test-Title" target="_blank" provider="article">Test-Content</sulu:link>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'content' => 'Test-Content', 'provider' => 'article'],
                $tag2 => ['href' => '312-312-312', 'title' => 'Test-Title', 'provider' => 'article'],
                $tag3 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                $tag4 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="/de/test-1" title="Page-Title 1">Test-Content</a>',
                $tag2 => '<a href="/de/test-2" title="Test-Title">Page-Title 2</a>',
                $tag3 => '<a href="/de/test-1" title="Test-Title">Test-Content</a>',
                $tag4 => '<a href="/de/test-1" title="Test-Title" target="_blank">Test-Content</a>',
            ],
            $result
        );
    }

    public function testParseAllMultipleProvider()
    {
        $this->providers['page']->preload(['312-312-312'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('312-312-312', 'Page-Title 1', '/de/test-1', true),
                ]
            );
        $this->providers['article']->preload(['123-123-123'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 2', '/de/test-2', true),
                ]
            );

        $tag1 = '<sulu:link href="123-123-123" provider="article"/>';
        $tag2 = '<sulu:link href="312-312-312" provider="page"/>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'provider' => 'article'],
                $tag2 => ['href' => '312-312-312', 'provider' => 'page'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="/de/test-2" title="Page-Title 2">Page-Title 2</a>',
                $tag2 => '<a href="/de/test-1" title="Page-Title 1">Page-Title 1</a>',
            ],
            $result
        );
    }

    public function testParseAllMultipleProviderSameId()
    {
        $this->providers['page']->preload(['123-123-123'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 1', '/de/test-1', true),
                ]
            );
        $this->providers['article']->preload(['123-123-123'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 2', '/de/test-2', true),
                ]
            );

        $tag1 = '<sulu:link href="123-123-123" provider="article"/>';
        $tag2 = '<sulu:link href="123-123-123" provider="page"/>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'provider' => 'article'],
                $tag2 => ['href' => '123-123-123', 'provider' => 'page'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="/de/test-2" title="Page-Title 2">Page-Title 2</a>',
                $tag2 => '<a href="/de/test-1" title="Page-Title 1">Page-Title 1</a>',
            ],
            $result
        );
    }

    public function testParseAllMultipleTagsMissingContent()
    {
        $tag1 = '<sulu:link href="123-123-123" provider="article">Test-Content</sulu:link>';
        $tag2 = '<sulu:link href="123-123-123" title="Test-Title" provider="article"/>';
        $tag3 = '<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>';
        $tag4 = '<sulu:link href="123-123-123" provider="article"/>';

        $this->providers['article']->preload(['123-123-123'], 'de', true)->willReturn([]);

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'content' => 'Test-Content', 'provider' => 'article'],
                $tag2 => ['href' => '123-123-123', 'title' => 'Test-Title', 'provider' => 'article'],
                $tag3 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                $tag4 => ['href' => '123-123-123', 'provider' => 'article'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => 'Test-Content',
                $tag2 => 'Test-Title',
                $tag3 => 'Test-Content',
                $tag4 => '',
            ],
            $result
        );
    }

    public function testValidate()
    {
        $this->providers['article']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test', true),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals([], $result);
    }

    public function testValidateInvalid()
    {
        $this->providers['article']->preload(['123-123-123'], 'de', false)->willReturn([]);

        $result = $this->linkTag->validateAll(
            [
                '<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            ['<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>' => LinkTag::VALIDATE_REMOVED],
            $result
        );
    }

    public function testValidateUnpublished()
    {
        $this->providers['article']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test', false),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            ['<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>' => LinkTag::VALIDATE_UNPUBLISHED],
            $result
        );
    }

    public function testValidateMixed()
    {
        $this->providers['article']->preload(['123-123-123', '312-312-312', '123-456-789'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test', true),
                    new LinkItem('123-456-789', 'Page-Title', '/de/test', false),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu:link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu:link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                '<sulu:link href="312-312-312" title="Test-Title" provider="article">Test-Content</sulu:link>' => [
                    'href' => '312-312-312',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                '<sulu:link href="123-312-123" title="Test-Title" provider="article">Test-Content</sulu:link>' => [
                    'href' => '123-456-789',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                '<sulu:link href="312-312-312" title="Test-Title" provider="article">Test-Content</sulu:link>' => LinkTag::VALIDATE_REMOVED,
                '<sulu:link href="123-312-123" title="Test-Title" provider="article">Test-Content</sulu:link>' => LinkTag::VALIDATE_UNPUBLISHED,
            ],
            $result
        );
    }

    public function testValidateAllMultipleProviders()
    {
        $this->providers['page']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test-1', true),
                ]
            );
        $this->providers['article']->preload([1], 'de', false)
            ->willReturn(
                [
                    new LinkItem(1, 'Article-Title', '/de/test-2', true),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu:link href="1" provider="article"/>' => [
                    'href' => 1,
                    'provider' => 'article',
                ],
                '<sulu:link href="123-123-123" provider="page"/>' => [
                    'href' => '123-123-123',
                    'provider' => 'page',
                ],
            ],
            'de'
        );

        $this->assertEquals([], $result);
    }

    public function testValidateAllMultipleProvidersSameId()
    {
        $this->providers['page']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test-1', true),
                ]
            );
        $this->providers['article']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Article-Title', '/de/test-2', true),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu:link href="123-123-123" provider="article"/>' => [
                    'href' => '123-123-123',
                    'provider' => 'article',
                ],
                '<sulu:link href="123-123-123" provider="page"/>' => [
                    'href' => '123-123-123',
                    'provider' => 'page',
                ],
            ],
            'de'
        );

        $this->assertEquals([], $result);
    }
}
