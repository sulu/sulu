<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Types\TextEditor;

class TextEditorTest extends \PHPUnit_Framework_TestCase
{
    const VALIDATE_REMOVED = 'removed';
    const VALIDATE_UNPUBLISHED = 'unpublished';

    /**
     * @var string
     */
    private $template;

    /**
     * @var MarkupParserInterface
     */
    private $markupParser;

    /**
     * @var TextEditor
     */
    private $textEditor;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var PropertyInterface
     */
    private $property;

    public function setUp()
    {
        $this->markupParser = $this->prophesize(MarkupParserInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);

        $this->textEditor = new TextEditor($this->template, $this->markupParser->reveal());
    }

    public function testRead()
    {
        $content = <<<'EOT'
<sulu:link href="123">Hello Hikaro Sulu</sulu:link>
EOT;

        $this->property->getName()->willReturn('i18n:de-description');
        $this->node->getPropertyValueWithDefault('i18n:de-description', '')->willReturn($content);

        $this->markupParser->validate($content, 'de')->willReturn([]);

        $this->property->setValue(
            <<<'EOT'
<sulu:link href="123">Hello Hikaro Sulu</sulu:link>
EOT
        )->shouldBeCalled();

        $this->textEditor->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testReadInvalid()
    {
        $content = <<<'EOT'
<sulu:link href="123">Hello</sulu:link>
<sulu:link href="456">Hikaro</sulu:link>
<sulu:link href="789">Sulu</sulu:link>
EOT;

        $this->property->getName()->willReturn('i18n:de-description');
        $this->node->getPropertyValueWithDefault('i18n:de-description', '')->willReturn($content);

        $this->markupParser->validate($content, 'de')->willReturn(
            [
                '<sulu:link href="123">Hello</sulu:link>' => self::VALIDATE_REMOVED,
                '<sulu:link href="789">Sulu</sulu:link>' => self::VALIDATE_UNPUBLISHED,
            ]
        );

        $this->property->setValue(
            <<<'EOT'
<sulu:link href="123" sulu:validation-state="removed">Hello</sulu:link>
<sulu:link href="456">Hikaro</sulu:link>
<sulu:link href="789" sulu:validation-state="unpublished">Sulu</sulu:link>
EOT
        )->shouldBeCalled();

        $this->textEditor->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testWrite()
    {
        $content = <<<'EOT'
<sulu:link href="123">Hello</sulu:link>
<sulu:link href="456">Hikaro</sulu:link>
<sulu:link href="789">Sulu</sulu:link>
EOT;

        $this->property->getName()->willReturn('i18n:de-description');
        $this->property->getValue()->willReturn(
            <<<'EOT'
<sulu:link href="123" sulu:validation-state="removed">Hello</sulu:link>
<sulu:link href="456">Hikaro</sulu:link>
<sulu:link href="789" sulu:validation-state="unpublished">Sulu</sulu:link>
EOT
        );

        $this->node->setProperty('i18n:de-description', $content);
        $this->textEditor->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteNoValue()
    {
        $this->property->getName()->willReturn('i18n:de-description');
        $this->property->getValue()->willReturn(null);

        $this->node->remove('i18n:de-description');
        $this->textEditor->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }
}
