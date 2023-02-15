<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Command\ValidateWebspacesCommand;
use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\StructureProvider\WebspaceStructureProvider;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class ValidateWebspacesCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<Environment>
     */
    private $twig;

    /**
     * @var ObjectProphecy<StructureMetadataFactory>
     */
    private $structureMetadataFactory;

    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private $structureManager;

    /**
     * @var ObjectProphecy<WebspaceStructureProvider>
     */
    private $structureProvider;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $eventDispatcher;

    /**
     * @var ValidateWebspacesCommand
     */
    private $validateWebspacesCommand;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private $output;

    public function setUp(): void
    {
        $this->twig = $this->prophesize(Environment::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->structureProvider = $this->prophesize(WebspaceStructureProvider::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->validateWebspacesCommand = new ValidateWebspacesCommand(
            $this->twig->reveal(),
            $this->structureMetadataFactory->reveal(),
            null,
            $this->structureManager->reveal(),
            $this->structureProvider->reveal(),
            $this->webspaceManager->reveal(),
            $this->eventDispatcher->reveal()
        );

        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
    }

    public function testExecute(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $webspace->addLocalization(new Localization('de'));

        $this->structureManager->getStructures()->willReturn([]);
        $this->webspaceManager->getWebspaceCollection()->willReturn(new WebspaceCollection([$webspace]));

        $executeMethod = new \ReflectionMethod(ValidateWebspacesCommand::class, 'execute');
        $executeMethod->setAccessible(true);

        $this->eventDispatcher->dispatch(new PreRenderEvent(new RequestAttributes([
            'webspace' => $webspace,
        ])), Events::PRE_RENDER)
            ->shouldBeCalled();

        $executeMethod->invoke($this->validateWebspacesCommand, $this->input->reveal(), $this->output->reveal());
    }
}
