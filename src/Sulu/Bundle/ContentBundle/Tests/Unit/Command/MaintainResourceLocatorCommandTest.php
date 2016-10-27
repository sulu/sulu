<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Command;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Command\MaintainResourceLocatorCommand;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintainResourceLocatorCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var StructureMetadataFactory
     */
    private $structureMetadataFactory;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var MaintainResourceLocatorCommand
     */
    private $maintainResourceLocatorCommand;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    public function setUp()
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactory::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->maintainResourceLocatorCommand = new MaintainResourceLocatorCommand(
            $this->webspaceManager->reveal(),
            $this->sessionManager->reveal(),
            $this->liveSession->reveal(),
            $this->metadataFactory->reveal(),
            $this->structureMetadataFactory->reveal(),
            $this->propertyEncoder->reveal()
        );

        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
    }

    public function testExecute()
    {
        $webspace1 = new Webspace();
        $webspace1->setKey('sulu_io');
        $webspace1->addLocalization(new Localization('de'));

        $this->webspaceManager->getWebspaceCollection()->willReturn([$webspace1]);

        $this->sessionManager->getContentPath('sulu_io')->willReturn('/cmf/sulu_io/contents');
        $this->sessionManager->getRoutePath('sulu_io', 'de')->willReturn('/cmf/sulu_io/routes/de');
        $this->propertyEncoder->localizedSystemName('template', 'de')->willReturn('i18n:de-template');
        $this->propertyEncoder->localizedSystemName('nodeType', 'de')->willReturn('i18n:de-nodeType');
        $this->propertyEncoder->localizedContentName('url', 'de')->willReturn('i18n:de-url');
        $this->propertyEncoder->localizedContentName('title', 'de')->willReturn('i18n:de-title');

        $liveContentNode = $this->prophesize(NodeInterface::class);
        $this->liveSession->getNode('/cmf/sulu_io/contents')->willReturn($liveContentNode);
        $this->liveSession->save()->shouldBeCalled();

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $property = $this->prophesize(PropertyInterface::class);
        $property->getContentTypeName()->willReturn('resource_locator');
        $property->getName()->willReturn('url');
        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getPropertyByTagName('sulu.rlp')->willReturn($property->reveal());

        $this->metadataFactory->getMetadataForPhpcrNode($liveContentNode->reveal())->willReturn($metadata);
        $liveContentNode->getPropertyValue('i18n:de-template')->willReturn('default');
        $liveContentNode->hasProperty('i18n:de-template')->willReturn(false);
        $liveContentNode->getPropertyValue('i18n:de-nodeType')->shouldNotBeCalled();
        $liveContentNode->getReferences('sulu:content')->shouldNotBeCalled();
        $this->structureMetadataFactory->getStructureMetadata('page', 'default')
            ->willReturn($structureMetadata->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $this->metadataFactory->getMetadataForPhpcrNode($node->reveal())->willReturn($metadata);
        $node->getPropertyValue('i18n:de-template')->willReturn('default');
        $node->hasProperty('i18n:de-template')->willReturn(true);
        $node->getPropertyValue('i18n:de-nodeType')->willReturn(RedirectType::NONE);
        $node->getPath()->willReturn('/cmf/sulu_io/contents/test');
        $node->getPropertyValue('i18n:de-title')->willReturn('test');

        $historyRouteProperty = $this->prophesize(NodeInterface::class);
        $historyRouteProperty->getPath()->willReturn('/cmf/sulu_io/routes/de/test/sulu:content');
        $historyRouteNode = $this->prophesize(NodeInterface::class);
        $historyRouteNode->getPropertyValue('sulu:history')->willReturn(true);
        $historyRouteProperty->getParent()->willReturn($historyRouteNode->reveal());

        $routeProperty = $this->prophesize(NodeInterface::class);
        $routeProperty->getPath()->willReturn('/cmf/sulu_io/routes/de/testng/sulu:content');
        $routeNode = $this->prophesize(NodeInterface::class);
        $routeNode->getPropertyValue('sulu:history')->willReturn(false);
        $routeNode->getPath()->willReturn('/cmf/sulu_io/routes/de/testing');
        $routeProperty->getParent()->willReturn($routeNode->reveal());
        $node->getReferences('sulu:content')->willReturn([$historyRouteProperty->reveal(), $routeProperty->reveal()]);

        $node->getNodes()->willReturn([]);
        $this->structureMetadataFactory->getStructureMetadata('page', 'default')
            ->willReturn($structureMetadata->reveal());
        $liveContentNode->getNodes()->willReturn([$node->reveal()]);

        $node->setProperty('i18n:de-url', '/testing')->shouldBeCalled();

        $executeMethod = new \ReflectionMethod($this->maintainResourceLocatorCommand, 'execute');
        $executeMethod->setAccessible(true);

        $executeMethod->invoke($this->maintainResourceLocatorCommand, $this->input->reveal(), $this->output->reveal());
    }
}
