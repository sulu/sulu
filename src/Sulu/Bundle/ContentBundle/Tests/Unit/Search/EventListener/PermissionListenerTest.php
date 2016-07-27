<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Event\PermissionUpdateEvent;

class PermissionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionListener
     */
    private $permissionListener;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);

        $this->permissionListener = new PermissionListener(
            $this->documentManager->reveal(),
            $this->searchManager->reveal()
        );
    }

    public function testOnPermissionUpdate()
    {
        $document = new \stdClass();
        $event = new PermissionUpdateEvent(SecurityBehavior::class, '1', null);

        $this->documentManager->find('1')->willReturn($document);
        $this->searchManager->deindex($document)->shouldBeCalled();

        $this->permissionListener->onPermissionUpdate($event);
    }

    public function testOnPermissionUpdateNotSecured()
    {
        $event = new PermissionUpdateEvent(\stdClass::class, '1', null);

        $this->searchManager->deindex(Argument::any())->shouldNotBeCalled();

        $this->permissionListener->onPermissionUpdate($event);
    }
}
