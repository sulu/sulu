<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Twig;

use Sulu\Bundle\ContentBundle\Twig\ContentTwigExtension;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

class ContentTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var ContentTwigExtension
     */
    private $contentTwigExtension;

    public function setUp()
    {
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->contentTwigExtension = new ContentTwigExtension($this->contentTypeManager->reveal());
    }

    public function provideNeedsAddButton()
    {
        return [
            [0, 1, true],
            [10, 10, false],
            [10, 20, true],
            [10, null, true],
        ];
    }

    /**
     * @dataProvider provideNeedsAddButton
     */
    public function testNeedsAddButton($minOccurs, $maxOccurs, $result)
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getMinOccurs()->willReturn($minOccurs);
        $property->getMaxOccurs()->willReturn($maxOccurs);

        $this->assertEquals($result, $this->contentTwigExtension->needsAddButtonFunction($property->reveal()));
    }

    public function provideTestIsMultiple()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider provideTestIsMultiple
     */
    public function testIsMultiple($value)
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getIsMultiple()->willReturn($value);

        $this->assertEquals($value, $this->contentTwigExtension->isMultipleTest($property->reveal()));
    }
}
