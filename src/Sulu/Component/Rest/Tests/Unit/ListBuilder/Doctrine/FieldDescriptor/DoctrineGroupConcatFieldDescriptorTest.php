<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine\FieldDescriptor;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineGroupConcatFieldDescriptor;

class DoctrineGroupConcatFieldDescriptorTest extends TestCase
{
    public function testGetSelect(): void
    {
        $doctrineGroupConcatFieldDescriptor = new DoctrineGroupConcatFieldDescriptor(
            new DoctrineFieldDescriptor('test', '', 'TestEntity'),
            'test'
        );

        $this->assertEquals(
            'GROUP_CONCAT(TestEntity.test SEPARATOR \',\')',
            $doctrineGroupConcatFieldDescriptor->getSelect()
        );
    }

    public function testGetSelectWithGlue(): void
    {
        $doctrineGroupConcatFieldDescriptor = new DoctrineGroupConcatFieldDescriptor(
            new DoctrineFieldDescriptor('test', '', 'TestEntity'),
            'test',
            '',
            ', '
        );

        $this->assertEquals(
            'GROUP_CONCAT(TestEntity.test SEPARATOR \', \')',
            $doctrineGroupConcatFieldDescriptor->getSelect()
        );
    }
}
