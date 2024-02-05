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
    use RequestParametersTrait;

    public function testGetRequestParameter(): void
    {
        $request = new Request(['test' => 'data']);

        $this->assertEquals('data', $this->getRequestParameter($request, 'test'));
        $this->assertEquals('data', $this->getRequestParameter($request, 'test', true));
    }

    public function testGettingParameterWithDefault(): void
    {
        $request = new Request(['test' => 'data']);

        $this->assertEquals(
            'default',
            $this->getRequestParameter($request, 'not_existing', false, 'default')
        );
    }

    public function testForceAndDefaultValue(): void
    {
        $request = new Request([]);

        $this->assertEquals('hello', $this->getRequestParameter($request, 'not_existing', true, 'hello'));
    }

    public function testGetRequestParameterFail(): void
    {
        $this->expectException(MissingParameterException::class);

        $request = new Request();

        $this->getBooleanRequestParameter($request, 'test', true);
    }

    public function testGetBooleanRequestParameter(): void
    {
        $request = new Request(['test1' => 'true', 'test2' => 'false']);

        $this->assertTrue($this->getBooleanRequestParameter($request, 'test1'));
        $this->assertTrue($this->getBooleanRequestParameter($request, 'test1', true));
        $this->assertFalse($this->getBooleanRequestParameter($request, 'test2'));
        $this->assertFalse($this->getBooleanRequestParameter($request, 'test2', true));
        $this->assertTrue($this->getBooleanRequestParameter($request, 'none', false, true));
        $this->assertNull($this->getBooleanRequestParameter($request, 'none', false));
    }

    public function testGetBooleanRequestParameterFail(): void
    {
        $this->expectException(MissingParameterException::class);

        $request = $this->prophesize(Request::class);

        $this->getBooleanRequestParameter($request->reveal(), 'test', true);
    }

    public function testGetBooleanRequestWrongParameter(): void
    {
        $this->expectException(ParameterDataTypeException::class);

        $request = $this->prophesize(Request::class);
        $request->get('test', null)->willReturn('asdf');

        $this->getBooleanRequestParameter($request->reveal(), 'test', true);
    }
}
