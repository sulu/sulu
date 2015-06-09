<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Content\Security\Listener;

use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Event\PermissionUpdateEvent;

class PermissionUpdateListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionUpdateListener
     */
    private $permissionUpdateListener;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->permissionUpdateListener = new PermissionUpdateListener($this->documentManager->reveal());
    }

    public function testOnPermissionUpdate()
    {
        $document = new BasePageDocument();
        $this->documentManager->find('id')->willReturn($document);

        $this->documentManager->persist($document, Argument::any());

        $this->permissionUpdateListener->onPermissionUpdate(
            new PermissionUpdateEvent(WebspaceBehavior::class, 'id', array(''))
        );

        $this->assertEquals(array(''), $document->getPermissions());
    }

    public function testOnPermissionUpdateWithoutWebspaceBehavior()
    {
        $this->documentManager->find(Argument::any())->shouldNotBeCalled();

        $this->permissionUpdateListener->onPermissionUpdate(new PermissionUpdateEvent('', '', array()));
    }
}
