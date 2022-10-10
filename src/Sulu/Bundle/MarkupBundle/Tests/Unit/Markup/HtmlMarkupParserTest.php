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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Markup\DelegatingTagExtractor;
use Sulu\Bundle\MarkupBundle\Markup\HtmlMarkupParser;
use Sulu\Bundle\MarkupBundle\Markup\HtmlTagExtractor;
use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagNotFoundException;
use Sulu\Bundle\MarkupBundle\Tag\TagRegistryInterface;

class HtmlMarkupParserTest extends TestCase
{
    use ProphecyTrait;

    public const VALIDATE_UNPUBLISHED = 'unpublished';

    public const VALIDATE_REMOVED = 'removed';

    /**
     * @var ObjectProphecy<TagInterface>
     */
    private $linkTag;

    /**
     * @var ObjectProphecy<TagInterface>
     */
    private $mediaTag;

    /**
     * @var ObjectProphecy<TagRegistryInterface>
     */
    private $tagRegistry;

    /**
     * @var HtmlMarkupParser
     */
    private $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->linkTag = $this->prophesize(TagInterface::class);
        $this->mediaTag = $this->prophesize(TagInterface::class);
        $this->tagRegistry = $this->prophesize(TagRegistryInterface::class);
        $this->tagRegistry->getTag('link', 'html', 'sulu')->willReturn($this->linkTag->reveal());
        $this->tagRegistry->getTag('media', 'html', 'sulu')->willReturn($this->mediaTag->reveal());
        $this->tagRegistry->getTag(Argument::any())->willThrow(new TagNotFoundException('sulu', 'test', 'html'));

        $this->parser = new HtmlMarkupParser(
            $this->tagRegistry->reveal(),
            new DelegatingTagExtractor([new HtmlTagExtractor('sulu')])
        );
    }

    public function testParse(): void
    {
        $this->linkTag->parseAll(
            ['<sulu-link href="123-123-123" title="test" />' => ['href' => '123-123-123', 'title' => 'test']],
            'de'
        )->willReturn(
            ['<sulu-link href="123-123-123" title="test" />' => '<a href="/test" title="test">page title</a>']
        );

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test" />
    </body>
</html>
EOT;

        $response = $this->parser->parse($content, 'de');

        $this->assertStringContainsString('<a href="/test" title="test">page title</a>', $response);
    }

    public function testParseMultiple(): void
    {
        $this->linkTag->parseAll(
            [
                '<sulu-link href="123-123-123" title="test" />' => ['href' => '123-123-123', 'title' => 'test'],
                '<sulu-link href="312-312-312" title="test" />' => ['href' => '312-312-312', 'title' => 'test'],
            ],
            'de'
        )->willReturn(
            [
                '<sulu-link href="123-123-123" title="test" />' => '<a href="/test" title="test">page title</a>',
                '<sulu-link href="312-312-312" title="test" />' => '<a href="/test-2" title="test">page-2 title</a>',
            ]
        );

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test" />
        <sulu-link href="312-312-312" title="test" />
    </body>
</html>
EOT;

        $response = $this->parser->parse($content, 'de');

        $this->assertStringContainsString('<a href="/test" title="test">page title</a>', $response);
        $this->assertStringContainsString('<a href="/test-2" title="test">page-2 title</a>', $response);
    }

    public function testParseSame(): void
    {
        $this->linkTag->parseAll(
            ['<sulu-link href="123-123-123" title="test" />' => ['href' => '123-123-123', 'title' => 'test']],
            'de'
        )->willReturn(
            ['<sulu-link href="123-123-123" title="test" />' => '<a href="/test" title="test">page title</a>']
        )->shouldBeCalledTimes(1);

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test" />
        <sulu-link href="123-123-123" title="test" />
    </body>
</html>
EOT;

        $response = $this->parser->parse($content, 'de');

        $this->assertEquals(2, \preg_match_all('/<a href="\/test" title="test">page title<\/a>/', $response));
        $this->assertStringNotContainsString('<sulu-link href="123-123-123" title="test" />', $response);
    }

    public function testParseDifferentTags(): void
    {
        $this->linkTag->parseAll(
            ['<sulu-link href="123-123-123" title="test" />' => ['href' => '123-123-123', 'title' => 'test']],
            'de'
        )->willReturn(
            ['<sulu-link href="123-123-123" title="test" />' => '<a href="/test" title="test">page title</a>']
        );
        $this->mediaTag->parseAll(
            ['<sulu-media src="1" title="test" />' => ['src' => '1', 'title' => 'test']],
            'de'
        )->willReturn(
            ['<sulu-media src="1" title="test" />' => '<img src="/img/test.jpg" title="test"/>']
        );

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test" />
        <sulu-media src="1" title="test" />
    </body>
</html>
EOT;

        $response = $this->parser->parse($content, 'de');

        $this->assertStringContainsString('<a href="/test" title="test">page title</a>', $response);
        $this->assertStringContainsString('<img src="/img/test.jpg" title="test"/>', $response);
    }

    public function testParseNonEmptyTag(): void
    {
        $this->linkTag->parseAll(
            [
                '<sulu-link href="123-123-123" title="test">link content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'test',
                    'content' => 'link content',
                ],
            ],
            'de'
        )->willReturn(
            ['<sulu-link href="123-123-123" title="test">link content</sulu-link>' => '<a href="/test" title="test">link content</a>']
        );

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test">link content</sulu-link>
    </body>
</html>
EOT;

        $response = $this->parser->parse($content, 'de');

        $this->assertStringContainsString('<a href="/test" title="test">link content</a>', $response);
    }

    public function testParseNested(): void
    {
        $this->linkTag->parseAll(
            [
                '<sulu-link href="123-123-123" title="test"><sulu-media id="1"/></sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'test',
                    'content' => '<sulu-media id="1"/>',
                ],
            ],
            'de'
        )->willReturn(
            ['<sulu-link href="123-123-123" title="test"><sulu-media id="1"/></sulu-link>' => '<a href="/test" title="test"><sulu-media id="1"/></a>']
        );

        $this->mediaTag->parseAll(['<sulu-media id="1"/>' => ['id' => 1]], 'de')
            ->willReturn(['<sulu-media id="1"/>' => '<img src="test.jpg"/>']);

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test"><sulu-media id="1"/></sulu-link>
    </body>
</html>
EOT;

        $response = $this->parser->parse($content, 'de');

        $this->assertStringContainsString('<a href="/test" title="test"><img src="test.jpg"/></a>', $response);
    }

    public function testValidate(): void
    {
        $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="test">link content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'test',
                    'content' => 'link content',
                ],
            ],
            'de'
        )->willReturn([]);

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test">link content</sulu-link>
    </body>
</html>
EOT;

        $response = $this->parser->validate($content, 'de');

        $this->assertEmpty($response);
    }

    public function testValidateInvalidTest(): void
    {
        $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="test">link content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'test',
                    'content' => 'link content',
                ],
            ],
            'de'
        )->willReturn(
            ['<sulu-link href="123-123-123" title="test">link content</sulu-link>' => self::VALIDATE_UNPUBLISHED]
        );

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test">link content</sulu-link>
    </body>
</html>
EOT;

        $response = $this->parser->validate($content, 'de');

        $this->assertCount(1, $response);
        $this->assertEquals(
            self::VALIDATE_UNPUBLISHED,
            $response['<sulu-link href="123-123-123" title="test">link content</sulu-link>']
        );
    }

    public function testValidateInvalidRemoved(): void
    {
        $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="test">link content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'test',
                    'content' => 'link content',
                ],
            ],
            'de'
        )->willReturn(
            ['<sulu-link href="123-123-123" title="test">link content</sulu-link>' => self::VALIDATE_REMOVED]
        );

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test">link content</sulu-link>
    </body>
</html>
EOT;

        $response = $this->parser->validate($content, 'de');

        $this->assertCount(1, $response);
        $this->assertEquals(
            self::VALIDATE_REMOVED,
            $response['<sulu-link href="123-123-123" title="test">link content</sulu-link>']
        );
    }

    public function testValidateDifferentInvalidTags(): void
    {
        $this->linkTag->validateAll(
            ['<sulu-link href="123-123-123" title="test"/>' => ['href' => '123-123-123', 'title' => 'test']],
            'de'
        )->willReturn(
            ['<sulu-link href="123-123-123" title="test"/>' => self::VALIDATE_REMOVED]
        );
        $this->mediaTag->validateAll(
            ['<sulu-media src="1" title="test"/>' => ['src' => '1', 'title' => 'test']],
            'de'
        )->willReturn(
            ['<sulu-media src="1" title="test"/>' => self::VALIDATE_UNPUBLISHED]
        );

        $content = <<<'EOT'
<html>
    <body>
        <sulu-link href="123-123-123" title="test"/>
        <sulu-media src="1" title="test"/>
    </body>
</html>
EOT;

        $response = $this->parser->validate($content, 'de');

        $this->assertCount(2, $response);
        $this->assertEquals(
            self::VALIDATE_REMOVED,
            $response['<sulu-link href="123-123-123" title="test"/>']
        );
        $this->assertEquals(
            self::VALIDATE_UNPUBLISHED,
            $response['<sulu-media src="1" title="test"/>']
        );
    }
}
