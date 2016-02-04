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

use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineGroupConcatFieldDescriptor;

class DoctrineGroupConcatFieldDescriptorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSelect()
    {
        $doctrineGroupConcatFieldDescriptor = new DoctrineGroupConcatFieldDescriptor(
            new DoctrineFieldDescriptor('test', null, 'TestEntity'),
            'test'
        );

        $this->assertEquals(
            'GROUP_CONCAT(TestEntity.test SEPARATOR \',\')',
            $doctrineGroupConcatFieldDescriptor->getSelect()
        );
    }

    public function testGetSelectWithGlue()
    {
        $doctrineGroupConcatFieldDescriptor = new DoctrineGroupConcatFieldDescriptor(
            new DoctrineFieldDescriptor('test', null, 'TestEntity'),
            'test',
            null,
            ', '
        );

        $this->assertEquals(
            'GROUP_CONCAT(TestEntity.test SEPARATOR \', \')',
            $doctrineGroupConcatFieldDescriptor->getSelect()
        );
    }
}
