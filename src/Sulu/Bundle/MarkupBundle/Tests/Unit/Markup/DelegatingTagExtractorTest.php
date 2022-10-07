<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Markup;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MarkupBundle\Markup\DelegatingTagExtractor;
use Sulu\Bundle\MarkupBundle\Markup\TagExtractorInterface;
use Sulu\Bundle\MarkupBundle\Markup\TagMatchGroup;

class DelegatingTagExtractorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var string
     */
    private $html = '<html><body><h1>Test</h1></body></html>';

    public function testCount(): void
    {
        $extractors = [
            $this->prophesize(TagExtractorInterface::class),
            $this->prophesize(TagExtractorInterface::class),
        ];

        $extractors[0]->count($this->html)->willReturn(11)->shouldBeCalledTimes(1);
        $extractors[1]->count($this->html)->willReturn(1)->shouldBeCalledTimes(1);

        $extractor = new DelegatingTagExtractor(
            \array_map(
                function($extrator) {
                    return $extrator->reveal();
                },
                $extractors
            )
        );

        $this->assertEquals(12, $extractor->count($this->html));
    }

    public function testExtract(): void
    {
        $extractors = [
            $this->prophesize(TagExtractorInterface::class),
            $this->prophesize(TagExtractorInterface::class),
        ];

        $extractors[0]->extract($this->html)->willReturn(
            [
                new TagMatchGroup('sulu', 'link', ['<sulu-link/>' => ['content' => '']]),
                new TagMatchGroup('sulu', 'media', ['<sulu-media id="1"/>' => ['content' => '', 'id' => 1]]),
            ]
        )->shouldBeCalledTimes(1);
        $extractors[1]->extract($this->html)->willReturn(
            [
                new TagMatchGroup(
                    'test',
                    'link',
                    ['<test:link href="123-123-123/>' => ['content' => '', 'href' => '123-123-123']]
                ),
                new TagMatchGroup('test', 'test', ['<test-test/>' => ['content' => '']]),
            ]
        )->shouldBeCalledTimes(1);

        $extractor = new DelegatingTagExtractor(
            \array_map(
                function($extrator) {
                    return $extrator->reveal();
                },
                $extractors
            )
        );

        $this->assertEquals(
            [
                new TagMatchGroup('sulu', 'link', ['<sulu-link/>' => ['content' => '']]),
                new TagMatchGroup('sulu', 'media', ['<sulu-media id="1"/>' => ['content' => '', 'id' => 1]]),
                new TagMatchGroup(
                    'test',
                    'link',
                    ['<test:link href="123-123-123/>' => ['content' => '', 'href' => '123-123-123']]
                ),
                new TagMatchGroup('test', 'test', ['<test-test/>' => ['content' => '']]),
            ],
            $extractor->extract($this->html)
        );
    }
}
