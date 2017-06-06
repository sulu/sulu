<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor;

class DoctrineFieldDescriptorBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineFielDescriptorBuilder
     */
    private $fieldDescriptorBuilder;

    public function setUp()
    {
        $this->fieldDescriptorBuilder = new DoctrineFieldDescriptorBuilder();
    }

    public function testBuildDefaultFieldDescriptor()
    {
        $fieldDescriptor = $this->fieldDescriptorBuilder->create('TestEntity', 'test')
            ->alias('test_alias')
            ->translate('public.test')
            ->join(
                new DoctrineJoinDescriptor('AnotherTestEntity', 'TestEntity.anotherTestEntity')
            )
            ->createColumnDescriptor()
            ->disable()
            ->default()
            ->type('thumbnail')
            ->enableSorting()
            ->enableEditing()
            ->getFieldDescriptor();
    }
}
