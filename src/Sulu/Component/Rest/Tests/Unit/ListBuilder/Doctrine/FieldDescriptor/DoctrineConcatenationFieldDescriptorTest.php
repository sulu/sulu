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
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

class DoctrineConcatenationFieldDescriptorTest extends TestCase
{
    public function testSelect(): void
    {
        $doctrineConcatenationFieldDescriptor = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor('test1', '', 'TestEntity1'),
                new DoctrineFieldDescriptor('test2', '', 'TestEntity2'),
                new DoctrineFieldDescriptor('test3', '', 'TestEntity3'),
            ],
            'test'
        );

        $this->assertEquals(
            'CONCAT(CONCAT(TestEntity1.test1, CONCAT(\' \', TestEntity2.test2)), CONCAT(\' \', TestEntity3.test3))',
            $doctrineConcatenationFieldDescriptor->getSelect()
        );
    }

    public function testSelectWithGlue(): void
    {
        $doctrineConcatenationFieldDescriptor = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor('test1', '', 'TestEntity1'),
                new DoctrineFieldDescriptor('test2', '', 'TestEntity2'),
                new DoctrineFieldDescriptor('test3', '', 'TestEntity3'),
            ],
            'test',
            'translation',
            ', '
        );

        $this->assertEquals(
            'CONCAT(CONCAT(TestEntity1.test1, CONCAT(\', \', TestEntity2.test2)), CONCAT(\', \', TestEntity3.test3))',
            $doctrineConcatenationFieldDescriptor->getSelect()
        );
    }
}
