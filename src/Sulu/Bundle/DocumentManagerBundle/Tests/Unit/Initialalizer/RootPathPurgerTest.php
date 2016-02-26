<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Initialalizer;

use Doctrine\Common\Persistence\ConnectionRegistry;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Initializer\RootPathPurger;
use Sulu\Component\DocumentManager\PathSegmentRegistry;

class RootPathPurgerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionInterface
     */
    private $session1;

    /**
     * @var SessionInterface
     */
    private $session2;

    /**
     * @var ConnectionRegistry
     */
    private $connectionRegistry;

    /**
     * @var PathSegmentRegistry
     */
    private $segmentRegistry;

    /**
     * @var RootPathPurger
     */
    private $purger;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->session1 = $this->prophesize(SessionInterface::class);
        $this->session2 = $this->prophesize(SessionInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->connectionRegistry = $this->prophesize(ConnectionRegistry::class);

        $this->segmentRegistry = $this->prophesize(PathSegmentRegistry::class);
        $this->purger = new RootPathPurger(
            $this->connectionRegistry->reveal(),
            $this->segmentRegistry->reveal()
        );
    }

    /**
     * It should purge the root path of all sessions.
     */
    public function testPurgeRootNodes()
    {
        $this->connectionRegistry->getConnections()->willReturn([
            $this->session1->reveal(),
            $this->session2->reveal(),
        ]);
        $this->segmentRegistry->getPathSegment('root')->willReturn('baff');

        $this->session1->nodeExists('/baff')->willReturn(false);
        $this->session2->nodeExists('/baff')->willReturn(true);
        $this->session2->getNode('/baff')->willReturn($this->node->reveal());
        $this->node->remove()->shouldBeCalled();
        $this->session2->save()->shouldBeCalled();

        $this->purger->purge();
    }
}
