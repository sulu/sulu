<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

class RestHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RestHelper
     */
    private $restHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $listRestHelper;

    public function setUp()
    {
        $this->listRestHelper = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\ListRestHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->restHelper = new RestHelper($this->listRestHelper);
    }

    public function testInitializeListBuilderLimit()
    {
        $listBuilder = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\AbstractListBuilder')
            ->getMock();

        $this->listRestHelper->expects($this->any())->method('getLimit')->willReturn(10);
        $listBuilder->expects($this->once())->method('limit')->with(10)->willReturnSelf();

        $this->restHelper->initializeListBuilder($listBuilder, array());
    }

    public function testInitializeListBuilderPage()
    {
        $listBuilder = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\AbstractListBuilder')
            ->getMock();

        $listBuilder->expects($this->any())->method('limit')->willReturnSelf();
        $this->listRestHelper->expects($this->any())->method('getPage')->willReturn(2);
        $listBuilder->expects($this->once())->method('setCurrentPage')->with(2)->willReturnSelf();

        $this->restHelper->initializeListBuilder($listBuilder, array());
    }

    public function testInitializeListBuilderAddFields()
    {
        $listBuilder = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\AbstractListBuilder')
            ->setMethods(array('addField'))
            ->getMockForAbstractClass();

        $field1 = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor')
            ->disableOriginalConstructor()
            ->getMock();

        $field2 = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listRestHelper->expects($this->any())->method('getFields')->willReturn(array('name', 'desc'));
        $listBuilder->expects($this->at(0))->method('addField')->with($field1);
        $listBuilder->expects($this->at(1))->method('addField')->with($field2);

        $this->restHelper->initializeListBuilder($listBuilder, array('name' => $field1, 'desc' => $field2));
    }

    public function testInitializeListBuilderSetFields()
    {
        $listBuilder = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\AbstractListBuilder')
            ->setMethods(array('setFields'))
            ->getMockForAbstractClass();

        $field1 = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor')
            ->disableOriginalConstructor()
            ->getMock();

        $field2 = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor')
            ->disableOriginalConstructor()
            ->getMock();

        $fields = array('name' => $field1, 'desc' => $field2);

        $listBuilder->expects($this->once())->method('setFields')->with($fields);

        $this->restHelper->initializeListBuilder($listBuilder, $fields);
    }

    public function testInitializeListBuilderAddSearch()
    {
        $listBuilder = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\AbstractListBuilder')
            ->setMethods(array('addSearchField', 'search'))
            ->getMockForAbstractClass();

        $field1 = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor')
            ->disableOriginalConstructor()
            ->getMock();

        $field2 = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listRestHelper->expects($this->any())->method('getSearchFields')->willReturn(array('name', 'desc'));
        $this->listRestHelper->expects($this->any())->method('getSearchPattern')->willReturn('searchValue');
        $listBuilder->expects($this->at(0))->method('addSearchField')->with($field1);
        $listBuilder->expects($this->at(1))->method('addSearchField')->with($field2);
        $listBuilder->expects($this->once())->method('search')->with('searchValue');

        $this->restHelper->initializeListBuilder($listBuilder, array('name' => $field1, 'desc' => $field2));
    }

    public function testInitializeListBuilderSort()
    {
        $listBuilder = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\AbstractListBuilder')
            ->setMethods(array('sort'))
            ->getMockForAbstractClass();

        $field = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listRestHelper->expects($this->any())->method('getSortColumn')->willReturn('name');
        $this->listRestHelper->expects($this->any())->method('getSortOrder')->willReturn('ASC');
        $listBuilder->expects($this->once())->method('sort')->with($field, 'ASC');

        $this->restHelper->initializeListBuilder($listBuilder, array('name' => $field));
    }
}
