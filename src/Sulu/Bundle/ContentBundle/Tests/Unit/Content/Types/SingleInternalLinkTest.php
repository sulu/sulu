<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Bundle\ContentBundle\Content\Types\SingleInternalLink;
use Sulu\Component\Content\PropertyInterface;

class SingleInternalLinkTest extends ProphecyTestCase
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
        $this->property = $this->prophesize('Sulu\Component\Content\PropertyInterface');

        $this->type = new SingleInternalLink(
            'some_template.html.twig'
        );
    }

    public function provideGetReferencedUuids()
    {
        return array(
            array(
                '4234-2345-2345-3245',
                array('4234-2345-2345-3245'),
            ),
            array(
                null,
                array(),
            ),
            array(
                '',
                array(),
            ),
        );
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
