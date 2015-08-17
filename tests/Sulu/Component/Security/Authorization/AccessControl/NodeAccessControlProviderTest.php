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

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class NodeAccessControlProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NodeAccessControlProvider
     */
    private $nodeAccessControlProvider;

    private $documentManager;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->nodeAccessControlProvider = new NodeAccessControlProvider(
            $this->documentManager->reveal(),
            ['view' => 64, 'edit' => 32, 'delete' => 16]
        );
    }

    public function testSetPermissions()
    {
        $document = $this->prophesize(WebspaceBehavior::class);
        $document->willImplement(SecurityBehavior::class);

        $this->documentManager->find('1')->willReturn($document);
        $document->setPermissions(['role' => ['view']])->shouldBeCalled();
        $this->documentManager->persist($document)->shouldBeCalled();
        $this->documentManager->flush()->shouldBeCalled();

        $this->nodeAccessControlProvider->setPermissions(
            get_class($document),
            '1',
            ['role' => ['view' => true, 'edit' => false]]
        );
    }

    public function testGetPermissions()
    {
        $document = $this->prophesize(WebspaceBehavior::class);
        $document->willImplement(SecurityBehavior::class);

        $this->documentManager->find('1')->willReturn($document);
        $document->getPermissions()->willReturn(['ROLE_USER' => ['view', 'edit']]);

        $this->assertEquals(
            ['ROLE_USER' => ['view' => true, 'edit' => true, 'delete' => false]],
            $this->nodeAccessControlProvider->getPermissions(get_class($document), '1')
        );
    }

    /**
     * @dataProvider provideSupport
     */
    public function testSupport($type, $supported)
    {
        $this->assertEquals($this->nodeAccessControlProvider->supports($type), $supported);
    }

    public function provideSupport()
    {
        return [
            [BasePageDocument::class, true],
            [\stdClass::class, false],
        ];
    }
}
