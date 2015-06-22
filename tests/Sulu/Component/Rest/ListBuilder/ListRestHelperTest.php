<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Symfony\Component\HttpFoundation\Request;

class ListRestHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $em;

    public function setUp()
    {
        $this->em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
    }

    public function testGetFields()
    {
        $request = new Request(array(
            'fields' => 'field1,field2,field3',
            'sortBy' => 'id',
            'sortOrder' => 'desc',
            'search' => 'test',
            'searchFields' => 'title',
            'limit' => 10,
            'page' => 3,
        ));
        $helper = new ListRestHelper($request, $this->em);

        $this->assertEquals(array('field1', 'field2', 'field3'), $helper->getFields());
        $this->assertEquals('id', $helper->getSortColumn());
        $this->assertEquals('desc', $helper->getSortOrder());
        $this->assertEquals('test', $helper->getSearchPattern());
        $this->assertEquals(array('title'), $helper->getSearchFields());
        $this->assertEquals(10, $helper->getLimit());
        $this->assertEquals(20, $helper->getOffset());
    }
}
