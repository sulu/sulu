<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Build;

use Massive\Bundle\BuildBundle\Build\BuilderContext;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\PageBundle\Build\NodeOrderBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Output\OutputInterface;

class NodeOrderBuilderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SessionManagerInterface>
     */
    private $sessionManager;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $defaultSession;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $liveSession;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    private $propertyEncoder;

    /**
     * @var ObjectProphecy<BuilderContext>
     */
    private $context;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private $output;

    /**
     * @var NodeOrderBuilder
     */
    private $nodeOrderBuilder;

    public function setUp(): void
    {
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->defaultSession = $this->prophesize(SessionInterface::class);
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->context = $this->prophesize(BuilderContext::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->context->getOutput()->willReturn($this->output->reveal());
        $this->propertyEncoder->systemName('order')->willReturn('sulu:order');

        $this->nodeOrderBuilder = new NodeOrderBuilder(
            $this->sessionManager->reveal(),
            $this->defaultSession->reveal(),
            $this->liveSession->reveal(),
            $this->webspaceManager->reveal(),
            $this->propertyEncoder->reveal()
        );

        $this->nodeOrderBuilder->setContext($this->context->reveal());
    }

    public function testBuild(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->webspaceManager->getWebspaceCollection()->willReturn(new WebspaceCollection(['sulu_io' => $webspace]));

        $this->sessionManager->getContentPath('sulu_io')->willReturn('/cmf/sulu_io/contents');

        $defaultRootWebspaceNode = $this->prophesize(NodeInterface::class);
        $defaultNode1 = $this->prophesize(NodeInterface::class);
        $childDefaultNode1 = $this->prophesize(NodeInterface::class);
        $childDefaultNode1->getNodes()->willReturn([]);
        $defaultNode1->getNodes()->willReturn([$childDefaultNode1->reveal()]);
        $defaultNode2 = $this->prophesize(NodeInterface::class);
        $defaultNode2->getNodes()->willReturn([]);
        $defaultRootWebspaceNode->getNodes()->willReturn([$defaultNode1->reveal(), $defaultNode2->reveal()]);
        $this->defaultSession->getNode('/cmf/sulu_io/contents')->willReturn($defaultRootWebspaceNode->reveal());

        $defaultNode1->getPath()->willReturn('/cmf/sulu_io/contents/default1');
        $childDefaultNode1->getPath()->willReturn('/cmf/sulu_io/contents/default1/child');
        $defaultNode2->getPath()->willReturn('/cmf/sulu_io/contents/default2');

        $liveRootWebspaceNode = $this->prophesize(NodeInterface::class);
        $liveNode1 = $this->prophesize(NodeInterface::class);
        $childLiveNode1 = $this->prophesize(NodeInterface::class);
        $childLiveNode1->getNodes()->willReturn([]);
        $liveNode1->getNodes()->willReturn([$childLiveNode1->reveal()]);
        $liveNode2 = $this->prophesize(NodeInterface::class);
        $liveNode2->getNodes()->willReturn([]);
        $liveRootWebspaceNode->getNodes()->willReturn([$liveNode1->reveal(), $liveNode2->reveal()]);
        $this->liveSession->getNode('/cmf/sulu_io/contents')->willReturn($liveRootWebspaceNode->reveal());

        $liveNode1->getPath()->willReturn('/cmf/sulu_io/contents/default1');
        $childLiveNode1->getPath()->willReturn('/cmf/sulu_io/contents/default1/child');
        $liveNode2->getPath()->willReturn('/cmf/sulu_io/contents/default2');

        $defaultNode1->setProperty('sulu:order', 10)->shouldBeCalled();
        $defaultNode2->setProperty('sulu:order', 20)->shouldBeCalled();
        $childDefaultNode1->setProperty('sulu:order', 10)->shouldBeCalled();

        $liveNode1->setProperty('sulu:order', 10)->shouldBeCalled();
        $liveNode2->setProperty('sulu:order', 20)->shouldBeCalled();
        $childLiveNode1->setProperty('sulu:order', 10)->shouldBeCalled();

        $this->defaultSession->save()->shouldBeCalled();
        $this->liveSession->save()->shouldBeCalled();

        $this->nodeOrderBuilder->build();
    }
}
