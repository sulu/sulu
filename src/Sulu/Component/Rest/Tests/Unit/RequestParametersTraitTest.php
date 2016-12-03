<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit;

use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ParameterDataTypeException;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Request;

class RequestParametersTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestParametersTrait
     */
    private $requestParametersTrait;

    public function setUp()
    {
        $this->requestParametersTrait = $this->getObjectForTrait('Sulu\Component\Rest\RequestParametersTrait');
    }

    private function getGetRequestParameterReflection()
    {
        $getRequestParameterReflection = new \ReflectionMethod(
            get_class($this->requestParametersTrait),
            'getRequestParameter'
        );

        $getRequestParameterReflection->setAccessible(true);

        return $getRequestParameterReflection;
    }

    private function getGetBooleanRequestParameterReflection()
    {
        $getBooleanRequestParameterReflection = new \ReflectionMethod(
            get_class($this->requestParametersTrait),
            'getBooleanRequestParameter'
        );

        $getBooleanRequestParameterReflection->setAccessible(true);

        return $getBooleanRequestParameterReflection;
    }

    public function testGetRequestParameter()
    {
        $request = $this->prophesize(Request::class);
        $request->get('test', null)->willReturn('data');
        $request->get('none', 'default')->willReturn('default');

        $getRequestParameterReflection = $this->getGetRequestParameterReflection();

        $this->assertEquals(
            'data',
            $getRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test')
        );

        $this->assertEquals(
            'data',
            $getRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test', true)
        );

        $this->assertEquals(
            'default',
            $getRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request->reveal(),
                'none',
                false,
                'default'
            )
        );
    }

    public function testGetRequestParameterFail()
    {
        $this->setExpectedException(MissingParameterException::class);

        $getRequestParameterReflection = $this->getGetRequestParameterReflection();
        $request = $this->prophesize(Request::class);

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test', true);
    }

    public function testGetBooleanRequestParameter()
    {
        $request = $this->prophesize(Request::class);
        $request->get('test1', null)->willReturn('true');
        $request->get('test2', null)->willReturn('false');
        $request->get('none', null)->willReturn(null);
        $request->get('none', true)->willReturn(true);

        $getBooleanRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test1')
        );

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request->reveal(),
                'test1',
                true
            )
        );

        $this->assertFalse(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test2')
        );

        $this->assertFalse(
            $getBooleanRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request->reveal(),
                'test2',
                true
            )
        );

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request->reveal(),
                'none',
                false,
                true
            )
        );

        $this->assertNull(
            $getBooleanRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request->reveal(),
                'none',
                false
            )
        );
    }

    public function testGetBooleanRequestParameterFail()
    {
        $this->setExpectedException(MissingParameterException::class);

        $getRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();
        $request = $this->prophesize(Request::class);

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test', true);
    }

    public function testGetBooleanRequestWrongParameter()
    {
        $this->setExpectedException(ParameterDataTypeException::class);

        $getRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();
        $request = $this->prophesize(Request::class);
        $request->get('test', null)->willReturn('asdf');

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test', true);
    }
}
