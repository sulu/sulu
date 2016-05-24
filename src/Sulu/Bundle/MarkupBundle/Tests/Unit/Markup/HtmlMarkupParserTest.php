<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Markup;

use Prophecy\Argument;
use Sulu\Bundle\MarkupBundle\Markup\HtmlMarkupParser;
use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagNotFoundException;
use Sulu\Bundle\MarkupBundle\Tag\TagRegistryInterface;

class HtmlMarkupParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TagInterface
     */
    private $linkTag;

    /**
     * @var TagInterface
     */
    private $mediaTag;

    /**
     * @var TagRegistryInterface
     */
    private $tagRegistry;

    /**
     * @var HtmlMarkupParser
     */
    private $parser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->linkTag = $this->prophesize(TagInterface::class);
        $this->mediaTag = $this->prophesize(TagInterface::class);
        $this->tagRegistry = $this->prophesize(TagRegistryInterface::class);
        $this->tagRegistry->getTag('link', 'html')->willReturn($this->linkTag->reveal());
        $this->tagRegistry->getTag('media', 'html')->willReturn($this->mediaTag->reveal());
        $this->tagRegistry->getTag(Argument::any())->willThrow(new TagNotFoundException('test', 'html'));

        $this->parser = new HtmlMarkupParser($this->tagRegistry->reveal());
    }

    public function testParse()
    {
        $this->linkTag->parseAll(
            ['<sulu:link href="123-123-123" title="test" />' => ['href' => '123-123-123', 'title' => 'test']]
        )->willReturn(
            ['<sulu:link href="123-123-123" title="test" />' => '<a href="/test" title="test">page title</a>']
        );

        $response = $this->parser->parse(
            <<<'EOT'
<html>
    <body>
        <sulu:link href="123-123-123" title="test" />
    </body>
</html>
EOT
        );

        $this->assertContains('<a href="/test" title="test">page title</a>', $response);
    }

    public function testParseMultiple()
    {
        $this->linkTag->parseAll(
            [
                '<sulu:link href="123-123-123" title="test" />' => ['href' => '123-123-123', 'title' => 'test'],
                '<sulu:link href="312-312-312" title="test" />' => ['href' => '312-312-312', 'title' => 'test'],
            ]
        )->willReturn(
            [
                '<sulu:link href="123-123-123" title="test" />' => '<a href="/test" title="test">page title</a>',
                '<sulu:link href="312-312-312" title="test" />' => '<a href="/test-2" title="test">page-2 title</a>',
            ]
        );

        $response = $this->parser->parse(
            <<<'EOT'
<html>
    <body>
        <sulu:link href="123-123-123" title="test" />
        <sulu:link href="312-312-312" title="test" />
    </body>
</html>
EOT
        );

        $this->assertContains('<a href="/test" title="test">page title</a>', $response);
        $this->assertContains('<a href="/test-2" title="test">page-2 title</a>', $response);
    }

    public function testParseSame()
    {
        $this->linkTag->parseAll(
            ['<sulu:link href="123-123-123" title="test" />' => ['href' => '123-123-123', 'title' => 'test']]
        )->willReturn(
            ['<sulu:link href="123-123-123" title="test" />' => '<a href="/test" title="test">page title</a>']
        )->shouldBeCalledTimes(1);

        $response = $this->parser->parse(
            <<<'EOT'
<html>
    <body>
        <sulu:link href="123-123-123" title="test" />
        <sulu:link href="123-123-123" title="test" />
    </body>
</html>
EOT
        );

        $this->assertEquals(2, preg_match_all('/<a href="\/test" title="test">page title<\/a>/', $response));
        $this->assertNotContains('<sulu:link href="123-123-123" title="test" />', $response);
    }

    public function testParseDifferentTags()
    {
        $this->linkTag->parseAll(
            ['<sulu:link href="123-123-123" title="test" />' => ['href' => '123-123-123', 'title' => 'test']]
        )->willReturn(
            ['<sulu:link href="123-123-123" title="test" />' => '<a href="/test" title="test">page title</a>']
        );
        $this->mediaTag->parseAll(
            ['<sulu:media src="1" title="test" />' => ['src' => '1', 'title' => 'test']]
        )->willReturn(
            ['<sulu:media src="1" title="test" />' => '<img src="/img/test.jpg" title="test"/>']
        );
        $this->mediaTag->parseAll(
            '<sulu:media src="1" title="test" />',
            ['src' => '1', 'title' => 'test']
        )->willReturn('<img src="/img/test.jpg" title="test"/>');

        $response = $this->parser->parse(
            <<<'EOT'
<html>
    <body>
        <sulu:link href="123-123-123" title="test" />
        <sulu:media src="1" title="test" />
    </body>
</html>
EOT
        );

        $this->assertContains('<a href="/test" title="test">page title</a>', $response);
        $this->assertContains('<img src="/img/test.jpg" title="test"/>', $response);
    }

    public function testParseNonEmptyTag()
    {
        $this->linkTag->parseAll(
            [
                '<sulu:link href="123-123-123" title="test">link content</sulu:link>' => [
                    'href' => '123-123-123',
                    'title' => 'test',
                    'content' => 'link content',
                ],
            ]
        )->willReturn(
            ['<sulu:link href="123-123-123" title="test">link content</sulu:link>' => '<a href="/test" title="test">link content</a>']
        );

        $response = $this->parser->parse(
            <<<'EOT'
<html>
    <body>
        <sulu:link href="123-123-123" title="test">link content</sulu:link>
    </body>
</html>
EOT
        );

        $this->assertContains('<a href="/test" title="test">link content</a>', $response);
    }
}
