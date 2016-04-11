<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Metadata;

abstract class ItemMetadataCase extends \PHPUnit_Framework_TestCase
{
    abstract public function getMetadata();

    /**
     * It should throw an exception if the named tag does not exist.
     *
     * @expectedException InvalidArgumentException
     */
    public function testGetTagNotExist()
    {
        $metadata = $this->getMetadata();
        $metadata->getTag('foo');
    }

    /**
     * It should get a named tag.
     */
    public function testGetTag()
    {
        $metadata = $this->getMetadata();
        $tag = ['name' => 'foo'];
        $metadata->tags = [$tag];
        $this->assertEquals($tag, $metadata->getTag('foo'));
    }

    /**
     * It should return a localized title.
     */
    public function testGetTitle()
    {
        $metadata = $this->getMetadata();
        $metadata->title['fr'] = 'Foobar';
        $this->assertEquals('Foobar', $metadata->getTitle('fr'));
    }

    /**
     * It should return the name if the localized title does not exist.
     */
    public function testGetTitleNoLocalization()
    {
        $metadata = $this->getMetadata();
        $metadata->name = 'foobar';
        $this->assertEquals('Foobar', $metadata->getTitle('es'));
    }

    /**
     * It get a parameter.
     */
    public function testGetParameters()
    {
        $metadata = $this->getMetadata();
        $metadata->parameters = [
            'param1' => 'param',
        ];
        $this->assertEquals('param', $metadata->getParameter('param1'));
    }

    /**
     * It throws an exception if the parameter does not exist.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknown parameter "param5", known parameters: "param1"
     */
    public function testGetParametersInvalid()
    {
        $metadata = $this->getMetadata();
        $metadata->parameters = [
            'param1' => 'param',
        ];
        $metadata->getParameter('param5');
    }
}
