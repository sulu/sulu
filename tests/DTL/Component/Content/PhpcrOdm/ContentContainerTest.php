<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\PhpcrOdm;

class ContentContainerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new ContentContainer(array(
            'hello' => 'goodbye',
            'one' => 2,
            'object' => new \stdClass,
            'array' => array(
                'thrity-three' => 33.00,
            ),
        ));
    }

    public function testPreSerialize()
    {
        $this->container->preSerialize();
        $typeMap = $this->container->getTypeMap();
        $this->assertEquals(array(
            'hello' => 'string',
            'one' => 'integer',
            'object' => 'stdClass',
            'array' => array(
                'thrity-three' => 'double',
            ),
        ), $typeMap);
    }
}
