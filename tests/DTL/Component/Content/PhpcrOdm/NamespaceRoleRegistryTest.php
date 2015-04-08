<?php

namespace vendor\sulu\sulu\tests\DTL\Component\Content\PhpcrOdm;

use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Component\Content\PhpcrOdm\NamespaceRoleRegistry;

class NamespaceRoleRegistryTest extends ProphecyTestCase
{
    public function testRegistry()
    {
        $registry = $this->createRegistry(array(
            'localized-content' => 'lcont',
            'content' => 'ncont'
        ));

        $this->assertEquals('lcont', $registry->getAlias('localized-content'));
        $this->assertEquals('ncont', $registry->getAlias('content'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Trying to get non-existant namespace alias role "foobar", known roles: "localized-content", "content"
     */
    public function testRegistryNotRegistered()
    {
        $registry = $this->createRegistry(array(
            'localized-content' => 'lcont',
            'content' => 'ncont'
        ));
        $registry->getAlias('foobar');
    }

    private function createRegistry($map)
    {
        return new NamespaceRoleRegistry($map);
    }
}
