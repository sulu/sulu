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
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCountFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

class DoctrineCountFieldDescriptorTest extends TestCase
{
    public function testGetSelect(): void
    {
        $doctrineCountFieldDescriptor = new DoctrineCountFieldDescriptor(
            'test',
            'test',
            'TestEntity',
            'translation',
            [],
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            '',
            true,
            false
        );

        $this->assertEquals(
            'COUNT(TestEntity.test)',
            $doctrineCountFieldDescriptor->getSelect()
        );
    }

    public function testGetSelectWithDistinct(): void
    {
        $doctrineCountFieldDescriptor = new DoctrineCountFieldDescriptor(
            'test',
            'test',
            'TestEntity',
            'translation',
            [],
            FieldDescriptorInterface::VISIBILITY_YES,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            '',
            true,
            true
        );

        $this->assertEquals(
            'COUNT(DISTINCT TestEntity.test)',
            $doctrineCountFieldDescriptor->getSelect()
        );
    }
}
