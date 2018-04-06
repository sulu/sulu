<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\FieldType;

use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistry;

class FieldTypeOptionRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldTypeOptionRegistry
     */
    private $fieldTypeOptionRegistry;

    public function setUp()
    {
        $this->fieldTypeOptionRegistry = new FieldTypeOptionRegistry();
    }

    public function testToArrayEmpty()
    {
        $this->assertEquals([], $this->fieldTypeOptionRegistry->toArray());
    }

    public function testToArray()
    {
        $this->fieldTypeOptionRegistry->add('snippet', 'assignment', ['resourceKey' => 'snippet']);
        $this->fieldTypeOptionRegistry->add('internal_links', 'assignment', ['resourceKey' => 'pages']);
        $this->fieldTypeOptionRegistry->add('test', 'test', []);

        $this->assertEquals([
            'assignment' => [
                'snippet' => [
                    'resourceKey' => 'snippet',
                ],
                'internal_links' => [
                    'resourceKey' => 'pages',
                ],
            ],
            'test' => [
                'test' => [],
            ],
        ], $this->fieldTypeOptionRegistry->toArray());
    }
}
