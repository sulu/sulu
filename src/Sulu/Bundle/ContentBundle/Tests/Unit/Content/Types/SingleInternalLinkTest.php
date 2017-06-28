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
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

class SingleInternalLinkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @var SingleInternalLink
     */
    private $type;

    public function setUp()
    {
        parent::setUp();

        $this->property = $this->prophesize(PropertyInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->type = new SingleInternalLink(
            $this->referenceStore->reveal(),
            'some_template.html.twig'
        );
    }

    public function providePreResolve()
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
     * @dataProvider providePreResolve
     */
    public function testPreResolve($propertyValue, $expected)
    {
        $this->property->getValue()->willReturn($propertyValue);
        $this->type->preResolve($this->property->reveal());

        foreach ($expected as $uuid) {
            $this->referenceStore->add($uuid)->shouldBeCalled();
        }
    }
}
