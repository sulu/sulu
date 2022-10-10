<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Session;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Session\SessionManager;

class SessionManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $defaultSession;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $liveSession;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    public function setUp(): void
    {
        $this->defaultSession = $this->prophesize(SessionInterface::class);
        $this->liveSession = $this->prophesize(SessionInterface::class);

        $this->sessionManager = new SessionManager($this->defaultSession->reveal(), $this->liveSession->reveal());
    }

    public function testSetNodeProperty(): void
    {
        $defaultNode = $this->prophesize(NodeInterface::class);
        $defaultNode->setProperty('settings:setting', 'data')->shouldBeCalled();
        $this->defaultSession->getNode('/cmf/sulu_io')->willReturn($defaultNode->reveal());

        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->setProperty('settings:setting', 'data')->shouldBeCalled();
        $this->liveSession->getNode('/cmf/sulu_io')->willReturn($liveNode->reveal());

        $this->sessionManager->setNodeProperty('/cmf/sulu_io', 'settings:setting', 'data');
    }

    public function testFlush(): void
    {
        $this->defaultSession->save()->shouldBeCalled();
        $this->liveSession->save()->shouldBeCalled();
        $this->sessionManager->flush();
    }
}
