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

use ReflectionMethod;

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

    private function getRequestMock(array $parametersValueMap)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $request->expects($this->any())->method('get')->will($this->returnValueMap($parametersValueMap));

        return $request;
    }

    private function getGetRequestParameterReflection()
    {
        $getRequestParameterReflection = new ReflectionMethod(
            get_class($this->requestParametersTrait),
            'getRequestParameter'
        );

        $getRequestParameterReflection->setAccessible(true);

        return $getRequestParameterReflection;
    }

    private function getGetBooleanRequestParameterReflection()
    {
        $getBooleanRequestParameterReflection = new ReflectionMethod(
            get_class($this->requestParametersTrait),
            'getBooleanRequestParameter'
        );

        $getBooleanRequestParameterReflection->setAccessible(true);

        return $getBooleanRequestParameterReflection;
    }

    public function testGetRequestParameter()
    {
        $request = $this->getRequestMock(
            [
                ['test', null, false, 'data'],
                ['none', 'default', false, 'default'],
            ]
        );

        $getRequestParameterReflection = $this->getGetRequestParameterReflection();

        $this->assertEquals(
            'data',
            $getRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test')
        );

        $this->assertEquals(
            'data',
            $getRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test', true)
        );

        $this->assertEquals(
            'default',
            $getRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'none', false, 'default')
        );
    }

    public function testGetRequestParameterFail()
    {
        $this->setExpectedException('Sulu\Component\Rest\Exception\MissingParameterException');

        $getRequestParameterReflection = $this->getGetRequestParameterReflection();
        $request = $this->getRequestMock([]);

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test', true);
    }

    public function testGetBooleanRequestParameter()
    {
        $request = $this->getRequestMock(
            [
                ['test1', null, false, 'true'],
                ['test2', null, false, 'false'],
                ['none', 'default', false, null],
            ]
        );

        $getBooleanRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test1')
        );

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test1', true)
        );

        $this->assertFalse(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test2')
        );

        $this->assertFalse(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test2', true)
        );

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'none', false, true)
        );

        $this->assertNull(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'none', false)
        );
    }

    public function testGetBooleanRequestParameterFail()
    {
        $this->setExpectedException('Sulu\Component\Rest\Exception\MissingParameterException');

        $getRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();
        $request = $this->getRequestMock([]);

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test', true);
    }

    public function testGetBooleanRequestWrongParameter()
    {
        $this->setExpectedException('Sulu\Component\Rest\Exception\ParameterDataTypeException');

        $getRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();
        $request = $this->getRequestMock(
            [
                ['test', null, false, 'asdf'],
            ]
        );

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test', true);
    }
}
