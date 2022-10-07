<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface as NodePropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Types\TextEditor;

class TextEditorTest extends TestCase
{
    use ProphecyTrait;

    public const VALIDATE_REMOVED = 'removed';

    public const VALIDATE_UNPUBLISHED = 'unpublished';

    /**
     * @var ObjectProphecy<MarkupParserInterface>
     */
    private $markupParser;

    /**
     * @var TextEditor
     */
    private $textEditor;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<PropertyInterface>
     */
    private $property;

    /**
     * @var ObjectProphecy<NodePropertyInterface>
     */
    private $nodeProperty;

    public function setUp(): void
    {
        $this->markupParser = $this->prophesize(MarkupParserInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->property = $this->prophesize(PropertyInterface::class);
        $this->nodeProperty = $this->prophesize(NodePropertyInterface::class);

        $this->textEditor = new TextEditor($this->markupParser->reveal());
    }

    public function testRead(): void
    {
        $content = <<<'EOT'
<sulu-link href="123">Hello Hikaro Sulu</sulu-link>
EOT;

        $this->property->getName()->willReturn('i18n:de-description');
        $this->node->getPropertyValueWithDefault('i18n:de-description', '')->willReturn($content);

        $this->markupParser->validate($content, 'de')->willReturn([]);

        $this->property->setValue(
            <<<'EOT'
<sulu-link href="123">Hello Hikaro Sulu</sulu-link>
EOT
        )->shouldBeCalled();

        $this->textEditor->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testReadInvalid(): void
    {
        $content = <<<'EOT'
<sulu-link href="123">Hello</sulu-link>
<sulu-link href="456">Hikaro</sulu-link>
<sulu-link href="789">Sulu</sulu-link>
EOT;

        $this->property->getName()->willReturn('i18n:de-description');
        $this->node->getPropertyValueWithDefault('i18n:de-description', '')->willReturn($content);

        $this->markupParser->validate($content, 'de')->willReturn(
            [
                '<sulu-link href="123">Hello</sulu-link>' => self::VALIDATE_REMOVED,
                '<sulu-link href="789">Sulu</sulu-link>' => self::VALIDATE_UNPUBLISHED,
            ]
        );

        $this->property->setValue(
            <<<'EOT'
<sulu-link href="123" sulu-validation-state="removed">Hello</sulu-link>
<sulu-link href="456">Hikaro</sulu-link>
<sulu-link href="789" sulu-validation-state="unpublished">Sulu</sulu-link>
EOT
        )->shouldBeCalled();

        $this->textEditor->read($this->node->reveal(), $this->property->reveal(), 'sulu_io', 'de', null);
    }

    public function testWrite(): void
    {
        $content = <<<'EOT'
<sulu-link href="123">Hello</sulu-link>
<sulu-link href="456">Hikaro</sulu-link>
<sulu-link href="789">Sulu</sulu-link>
EOT;

        $this->property->getName()->willReturn('i18n:de-description');
        $this->property->getValue()->willReturn(
            <<<'EOT'
<sulu-link href="123" sulu-validation-state="removed">Hello</sulu-link>
<sulu-link href="456">Hikaro</sulu-link>
<sulu-link href="789" sulu-validation-state="unpublished">Sulu</sulu-link>
EOT
        );

        $this->node->setProperty('i18n:de-description', $content)->shouldBeCalled();
        $this->textEditor->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }

    public function testWriteNoValue(): void
    {
        $this->property->getName()->willReturn('i18n:de-description');
        $this->property->getValue()->willReturn(null);
        $this->nodeProperty->remove()->shouldBeCalled();

        $this->node->hasProperty('i18n:de-description')->willReturn(true)->shouldBeCalled();
        $this->node->getProperty('i18n:de-description')->willReturn($this->nodeProperty->reveal())->shouldBeCalled();
        $this->textEditor->write($this->node->reveal(), $this->property->reveal(), 1, 'sulu_io', 'de', null);
    }
}
