<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine\FieldDescriptor;

use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

class DoctrineConcatenationFieldDescriptorTest extends \PHPUnit_Framework_TestCase
{
    public function testSelect()
    {
        $doctrineConcatenationFieldDescriptor = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor('test1', null, 'TestEntity1'),
                new DoctrineFieldDescriptor('test2', null, 'TestEntity2'),
                new DoctrineFieldDescriptor('test3', null, 'TestEntity3'),
            ],
            'test'
        );

        $this->assertEquals(
            'CONCAT(CONCAT(TestEntity1.test1, CONCAT(\' \', TestEntity2.test2)), CONCAT(\' \', TestEntity3.test3))',
            $doctrineConcatenationFieldDescriptor->getSelect()
        );
    }

    public function testSelectWithGlue()
    {
        $doctrineConcatenationFieldDescriptor = new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor('test1', null, 'TestEntity1'),
                new DoctrineFieldDescriptor('test2', null, 'TestEntity2'),
                new DoctrineFieldDescriptor('test3', null, 'TestEntity3'),
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
