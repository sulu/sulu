<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use Sulu\Bundle\ContentBundle\Content\Types\SingleInternalLink;
use Sulu\Component\Content\Compat\PropertyInterface;

class SingleInternalLinkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var SingleInternalLink
     */
    private $type;

    public function setUp()
    {
        parent::setUp();
        $this->property = $this->prophesize('Sulu\Component\Content\Compat\PropertyInterface');

        $this->type = new SingleInternalLink(
            'some_template.html.twig'
        );
    }

    public function provideGetReferencedUuids()
    {
        return [
            [
                '4234-2345-2345-3245',
                ['4234-2345-2345-3245'],
            ],
            [
                null,
                [],
            ],
            [
                '',
                [],
            ],
        ];
    }

    /**
     * @dataProvider provideGetReferencedUuids
     */
    public function testGetReferencedUuids($propertyValue, $expected)
    {
        $this->property->getValue()->willReturn($propertyValue);
        $uuids = $this->type->getReferencedUuids($this->property->reveal());
        $this->assertEquals($expected, $uuids);
    }
}
