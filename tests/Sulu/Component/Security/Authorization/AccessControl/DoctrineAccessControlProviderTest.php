<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Security\Authorization\AccessControl;

class DoctrineAccessControlProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAccessControlProvider
     */
    private $doctrineAccessControlProvider;

    public function setUp()
    {
        $this->doctrineAccessControlProvider = new DoctrineAccessControlProvider();
    }

    /**
     * @dataProvider provideSupport
     */
    public function testSupport($type, $supported)
    {
        $this->assertSame($supported, $this->doctrineAccessControlProvider->supports($type));
    }

    public function provideSupport()
    {
        $securedEntity = $this->prophesize(SecuredEntityInterface::class);

        return [
            [\stdClass::class, false],
            [get_class($securedEntity->reveal()), true],
        ];
    }
}
