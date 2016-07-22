<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Loader;

use Sulu\Component\Webspace\Loader\DelegatingFileLoader;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\Loader\LoaderInterface;

class DelegatingFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DelegatingFileLoader
     */
    private $delegatingFileLoader;

    /**
     * @var LoaderInterface
     */
    private $loader1;

    /**
     * @var LoaderInterface
     */
    private $loader2;

    public function setUp()
    {
        $this->loader1 = $this->prophesize(LoaderInterface::class);
        $this->loader2 = $this->prophesize(LoaderInterface::class);

        $this->delegatingFileLoader = new DelegatingFileLoader([
            $this->loader1->reveal(),
            $this->loader2->reveal(),
        ]);
    }

    public function testLoadFirstLoader()
    {
        $webspace = new Webspace();
        $this->loader1->supports('test.xml', null)->willReturn(true);
        $this->loader2->supports('test.xml', null)->shouldNotBeCalled();

        $this->loader1->load('test.xml', null)->willReturn($webspace);
        $this->loader2->load('test.xml', null)->shouldNotBeCalled();

        $this->assertSame($webspace, $this->delegatingFileLoader->load('test.xml'));
    }

    public function testLoadSecondLoader()
    {
        $webspace = new Webspace();
        $this->loader1->supports('test.xml', null)->willReturn(false);
        $this->loader2->supports('test.xml', null)->willReturn(true);

        $this->loader1->load('test.xml', null)->shouldNotBeCalled();
        $this->loader2->load('test.xml', null)->willReturn($webspace);

        $this->assertSame($webspace, $this->delegatingFileLoader->load('test.xml'));
    }

    public function testSupportFirstLoader()
    {
        $this->loader1->supports('test.xml', null)->willReturn(false);
        $this->loader2->supports('test.xml', null)->willReturn(true);

        $this->assertTrue($this->delegatingFileLoader->supports('test.xml'));
    }

    public function testSupportSecondLoader()
    {
        $this->loader1->supports('test.xml', null)->willReturn(false);
        $this->loader2->supports('test.xml', null)->willReturn(false);

        $this->assertFalse($this->delegatingFileLoader->supports('test.xml'));
    }
}
