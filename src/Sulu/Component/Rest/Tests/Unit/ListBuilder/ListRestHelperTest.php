<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder;

use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ListRestHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    public function setUp()
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
    }

    public static function dataFieldsProvider()
    {
        return [
            [
                new Request(
                    [
                        'fields' => 'field1,field2,field3',
                        'sortBy' => 'id',
                        'sortOrder' => 'desc',
                        'search' => 'test',
                        'searchFields' => 'title',
                        'limit' => 10,
                        'page' => 3,
                    ]
                ),
            ],
            [
                new Request(
                    [
                        'fields' => 'one,two,three',
                        'sortBy' => 'three',
                        'sortOrder' => 'asc',
                        'search' => 'now',
                        'searchFields' => 'title',
                        'limit' => 20,
                        'page' => 1,
                    ]
                ),
            ],
            [
                new Request(
                    [
                        'fields' => 'one,two,three',
                        'search' => 'now',
                        'searchFields' => 'title',
                        'limit' => 20,
                        'page' => 1,
                    ]
                ),
            ],
            [
                new Request(
                    [
                        'fields' => 'one,two,three',
                        'search' => 'now',
                        'searchFields' => 'title',
                        'limit' => 20,
                        'page' => 1,
                        '_format' => 'csv',
                    ]
                ),
            ],
            [
                new Request(
                    [
                        'fields' => 'one,two,three',
                        'search' => 'now',
                        'searchFields' => 'title',
                        'page' => 1,
                        '_format' => 'csv',
                    ]
                ),
            ],
        ];
    }

    /**
     * @dataProvider dataFieldsProvider
     */
    public function testGetFields($request)
    {
        $this->requestStack->getCurrentRequest()->willReturn($request);
        $helper = new ListRestHelper($this->requestStack->reveal());

        $this->assertEquals(explode(',', $request->get('fields')), $helper->getFields());
        $this->assertEquals($request->get('sortBy'), $helper->getSortColumn());
        $this->assertEquals($request->get('sortOrder', 'asc'), $helper->getSortOrder());
        $this->assertEquals($request->get('search'), $helper->getSearchPattern());
        $this->assertEquals(explode(',', $request->get('searchFields')), $helper->getSearchFields());
        $this->assertEquals($request->get('limit'), $helper->getLimit());
        $this->assertEquals($request->get('limit') * ($request->get('page') - 1), $helper->getOffset());
    }
}
