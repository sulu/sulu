<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ParameterDataTypeException;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Request;

class RequestParametersTraitTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var RequestParametersTrait
     */
    private $requestParametersTrait;

    public function setUp(): void
    {
        $this->requestParametersTrait = $this->getObjectForTrait('Sulu\Component\Rest\RequestParametersTrait');
    }

    private function getGetRequestParameterReflection()
    {
        $getRequestParameterReflection = new \ReflectionMethod(
            \get_class($this->requestParametersTrait),
            'getRequestParameter'
        );

        $getRequestParameterReflection->setAccessible(true);

        return $getRequestParameterReflection;
    }

    private function getGetBooleanRequestParameterReflection()
    {
        $getBooleanRequestParameterReflection = new \ReflectionMethod(
            \get_class($this->requestParametersTrait),
            'getBooleanRequestParameter'
        );

        $getBooleanRequestParameterReflection->setAccessible(true);

        return $getBooleanRequestParameterReflection;
    }

    public function testGetRequestParameter(): void
    {
        $request = new Request(['test' => 'data']);

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
            $getRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request,
                'none',
                false,
                'default'
            )
        );
    }

    public function testGetRequestParameterFail(): void
    {
        $this->expectException(MissingParameterException::class);

        $getRequestParameterReflection = $this->getGetRequestParameterReflection();
        $request = new Request();

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test', true);
    }

    public function testGetBooleanRequestParameter(): void
    {
        $request = new Request(['test1' => 'true', 'test2' => 'false']);

        $getBooleanRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test1')
        );

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request,
                'test1',
                true
            )
        );

        $this->assertFalse(
            $getBooleanRequestParameterReflection->invoke($this->requestParametersTrait, $request, 'test2')
        );

        $this->assertFalse(
            $getBooleanRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request,
                'test2',
                true
            )
        );

        $this->assertTrue(
            $getBooleanRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request,
                'none',
                false,
                true
            )
        );

        $this->assertNull(
            $getBooleanRequestParameterReflection->invoke(
                $this->requestParametersTrait,
                $request,
                'none',
                false
            )
        );
    }

    public function testGetBooleanRequestParameterFail(): void
    {
        $this->expectException(MissingParameterException::class);

        $getRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();
        $request = $this->prophesize(Request::class);

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test', true);
    }

    public function testGetBooleanRequestWrongParameter(): void
    {
        $this->expectException(ParameterDataTypeException::class);

        $getRequestParameterReflection = $this->getGetBooleanRequestParameterReflection();
        $request = $this->prophesize(Request::class);
        $request->get('test', null)->willReturn('asdf');

        $getRequestParameterReflection->invoke($this->requestParametersTrait, $request->reveal(), 'test', true);
    }
}
