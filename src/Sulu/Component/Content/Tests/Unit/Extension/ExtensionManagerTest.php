<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Extension;

use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManager;

class ExtensionManagerTest extends \PHPUnit_Framework_TestCase
{
    private function getExtension($name)
    {
        $extension = $this->prophesize(ExtensionInterface::class);
        $extension->getName()->willReturn($name);

        return $extension->reveal();
    }

    public function addProvider()
    {
        $instances = [
            $this->getExtension('test1'),
            $this->getExtension('test2'),
            $this->getExtension('test3'),
            $this->getExtension('test4'),
        ];

        $extensions = [
            ['instance' => $instances[0], 'type' => 'all'],
            ['instance' => $instances[1], 'type' => 'page'],
            ['instance' => $instances[2], 'type' => 'snippet'],
            ['instance' => $instances[3], 'type' => 'page'],
        ];

        return [
            [$extensions, 'page', ['test1' => $instances[0], 'test2' => $instances[1], 'test4' => $instances[3]]],
            [$extensions, 'snippet', ['test1' => $instances[0], 'test3' => $instances[2]]],
            [$extensions, 'home', ['test1' => $instances[0]]],
        ];
    }

    /**
     * @dataProvider addProvider
     */
    public function testAdd($extensions, $type, $expected)
    {
        $manager = new ExtensionManager();

        foreach ($extensions as $extension) {
            $manager->addExtension($extension['instance'], $extension['type']);
        }

        $this->assertEquals($expected, $manager->getExtensions($type));
    }

    public function hasProvider()
    {
        $instances = [
            $this->getExtension('test1'),
            $this->getExtension('test2'),
            $this->getExtension('test3'),
            $this->getExtension('test4'),
        ];

        $extensions = [
            ['instance' => $instances[0], 'type' => 'all'],
            ['instance' => $instances[1], 'type' => 'page'],
            ['instance' => $instances[2], 'type' => 'snippet'],
            ['instance' => $instances[3], 'type' => 'page'],
        ];

        return [
            [$extensions, 'page', 'test1', true],
            [$extensions, 'page', 'test2', true],
            [$extensions, 'page', 'test3', false],
            [$extensions, 'page', 'test4', true],
            [$extensions, 'snippet', 'test1', true],
            [$extensions, 'snippet', 'test2', false],
            [$extensions, 'snippet', 'test3', true],
            [$extensions, 'snippet', 'test4', false],
            [$extensions, 'home', 'test1', true],
            [$extensions, 'home', 'test2', false],
            [$extensions, 'home', 'test3', false],
            [$extensions, 'home', 'test4', false],
        ];
    }

    /**
     * @dataProvider hasProvider
     */
    public function testHas($extensions, $type, $name, $expected)
    {
        $manager = new ExtensionManager();

        foreach ($extensions as $extension) {
            $manager->addExtension($extension['instance'], $extension['type']);
        }

        $this->assertEquals($expected, $manager->hasExtension($type, $name));
    }

    public function getProvider()
    {
        $instances = [
            $this->getExtension('test1'),
            $this->getExtension('test2'),
            $this->getExtension('test3'),
            $this->getExtension('test4'),
        ];

        $extensions = [
            ['instance' => $instances[0], 'type' => 'all'],
            ['instance' => $instances[1], 'type' => 'page'],
            ['instance' => $instances[2], 'type' => 'snippet'],
            ['instance' => $instances[3], 'type' => 'page'],
        ];

        return [
            [$extensions, 'page', 'test1', $instances[0]],
            [$extensions, 'page', 'test2', $instances[1]],
            [$extensions, 'page', 'test4', $instances[3]],
            [$extensions, 'snippet', 'test1', $instances[0]],
            [$extensions, 'snippet', 'test3', $instances[2]],
            [$extensions, 'home', 'test1', $instances[0]],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($extensions, $type, $name, $expected)
    {
        $manager = new ExtensionManager();

        foreach ($extensions as $extension) {
            $manager->addExtension($extension['instance'], $extension['type']);
        }

        $this->assertEquals($expected, $manager->getExtension($type, $name));
    }

    public function getExceptionProvider()
    {
        $instances = [
            $this->getExtension('test1'),
            $this->getExtension('test2'),
            $this->getExtension('test3'),
            $this->getExtension('test4'),
        ];

        $extensions = [
            ['instance' => $instances[0], 'type' => 'all'],
            ['instance' => $instances[1], 'type' => 'page'],
            ['instance' => $instances[2], 'type' => 'snippet'],
            ['instance' => $instances[3], 'type' => 'page'],
        ];

        return [
            [$extensions, 'page', 'test3', \InvalidArgumentException::class],
            [$extensions, 'snippet', 'test2', \InvalidArgumentException::class],
            [$extensions, 'snippet', 'test4', \InvalidArgumentException::class],
            [$extensions, 'home', 'test2', \InvalidArgumentException::class],
            [$extensions, 'home', 'test3', \InvalidArgumentException::class],
            [$extensions, 'home', 'test4', \InvalidArgumentException::class],
        ];
    }

    /**
     * @dataProvider getExceptionProvider
     */
    public function testGetException($extensions, $type, $name, $exxceptionName)
    {
        $this->setExpectedException($exxceptionName);

        $manager = new ExtensionManager();

        foreach ($extensions as $extension) {
            $manager->addExtension($extension['instance'], $extension['type']);
        }

        $manager->getExtension($type, $name);
    }
}
