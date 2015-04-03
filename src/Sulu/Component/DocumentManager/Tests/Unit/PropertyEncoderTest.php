<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\DocumentManager\Tests\Unit;

use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\DocumentManager\Exception\MetadataNotFoundException;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\PropertyEncoder;

class PropertyEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $map = array(
            'system' => 'nsys',
            'system_localized' => 'lsys'
        );

        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->namespaceRegistry->getPrefix(Argument::type('string'))->will(function ($args) use ($map) {
            return $map[$args[0]];
        });
        $this->encoder = new PropertyEncoder($this->namespaceRegistry->reveal());
    }

    /**
     * It should encode localized system properties
     */
    public function testEncodeLocalizedSystem()
    {
        $name = $this->encoder->localizedSystemName('created', 'fr');
        $this->assertEquals('lsys:fr-created', $name);
    }

    /**
     * It should encode system properties
     */
    public function testEncodeSystem()
    {
        $name = $this->encoder->systemName('created');
        $this->assertEquals('nsys:created', $name);
    }
}

