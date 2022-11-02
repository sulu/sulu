<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\FieldType;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistry;

class FieldTypeOptionRegistryTest extends TestCase
{
    /**
     * @var FieldTypeOptionRegistry
     */
    private $fieldTypeOptionRegistry;

    public function setUp(): void
    {
        $this->fieldTypeOptionRegistry = new FieldTypeOptionRegistry();
    }

    public function testToArrayEmpty(): void
    {
        $this->assertEquals([], $this->fieldTypeOptionRegistry->toArray());
    }

    public function testToArray(): void
    {
        $this->fieldTypeOptionRegistry->add('snippet', 'selection', ['resourceKey' => 'snippet']);
        $this->fieldTypeOptionRegistry->add('page_selection', 'selection', ['resourceKey' => 'page']);
        $this->fieldTypeOptionRegistry->add('test', 'test', []);

        $this->assertEquals([
            'selection' => [
                'snippet' => [
                    'resourceKey' => 'snippet',
                ],
                'page_selection' => [
                    'resourceKey' => 'page',
                ],
            ],
            'test' => [
                'test' => [],
            ],
        ], $this->fieldTypeOptionRegistry->toArray());
    }
}
