<?php

namespace DTL\Component\Content\Extension;

use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManager;

class ExtensionManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->extensionManager = new ExtensionManager();

        $this->extension1 = $this->prophesize(ExtensionInterface::class);
        $this->extension2 = $this->prophesize(ExtensionInterface::class);
        $this->extension1->getName()->willReturn('ext1');
        $this->extension2->getName()->willReturn('ext2');

        $this->extensionManager->addExtension($this->extension1->reveal());
        $this->extensionManager->addExtension($this->extension2->reveal());
    }

    /**
     * It should say if the extension exists or not
     */
    public function testHasExtension()
    {
        $this->assertTrue($this->extensionManager->hasExtension('all', 'ext1'));
        $this->assertFalse($this->extensionManager->hasExtension('all', 'ext6'));
    }

    /**
     * It should retrieve a named extension
     */
    public function testGetExtension()
    {
        $this->assertSame(
            $this->extension1->reveal(),
            $this->extensionManager->getExtension('all', 'ext1')
        );
    }

    /**
     * It should retrieve all extensions
     */
    public function testGetExtensions()
    {
        $this->assertSame(
            array(
                'ext1' => $this->extension1->reveal(),
                'ext2' => $this->extension2->reveal(),
            ),
            $this->extensionManager->getExtensions('all')
        );
    }
}
