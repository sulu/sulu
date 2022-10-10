<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Subscriber\WebspaceSubscriber;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceSubscriberTest extends SubscriberTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $inspector;

    /**
     * @var WebspaceSubscriber
     */
    private $subscriber;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->subscriber = new WebspaceSubscriber(
            $this->encoder->reveal(),
            $this->inspector->reveal(),
            $this->documentManager->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    public function testHandleWebspace(): void
    {
        $document = $this->prophesize(WebspaceBehavior::class);
        $this->persistEvent->getDocument()->willReturn($document);

        $this->inspector->getWebspace($document->reveal())->willReturn('example');
        $this->accessor->set('webspaceName', 'example')->shouldBeCalled();

        $this->subscriber->handleWebspace($this->persistEvent->reveal());
    }

    public function testDeleteUnavailableLocales(): void
    {
        $copyEvent = $this->prophesize(CopyEvent::class);

        $document = $this->prophesize(WebspaceBehavior::class);
        $this->inspector->getLocale($document)->willReturn('fr');
        $copyEvent->getDocument()->willReturn($document->reveal());

        $copyEvent->getCopiedPath()->willReturn('/cmf/test_io/contents');

        $copiedDocument = $this->prophesize(WebspaceBehavior::class);
        $this->documentManager->find('/cmf/test_io/contents', 'fr')->willReturn($copiedDocument->reveal());
        $this->inspector->getWebspace($copiedDocument)->willReturn('test_io');
        $this->inspector->getLocales($copiedDocument)->willReturn(['en', 'fr', 'de']);

        $this->encoder->localizedContentName('*', 'fr')->willReturn('i18n:fr-*');
        $this->encoder->localizedContentName('*', 'en')->willReturn('i18n:en-*');
        $this->encoder->localizedContentName('*', 'de')->willReturn('i18n:de-*');

        $copiedNode = $this->prophesize(NodeInterface::class);

        $frenchProperty1 = $this->prophesize(PropertyInterface::class);
        $frenchProperty2 = $this->prophesize(PropertyInterface::class);
        $copiedNode->getProperties('i18n:fr-*')->willReturn([$frenchProperty1->reveal(), $frenchProperty2->reveal()]);

        $germanProperty1 = $this->prophesize(PropertyInterface::class);
        $germanProperty2 = $this->prophesize(PropertyInterface::class);
        $copiedNode->getProperties('i18n:de-*')->willReturn([$germanProperty1->reveal(), $germanProperty2->reveal()]);

        $englishProperty1 = $this->prophesize(PropertyInterface::class);
        $copiedNode->getProperties('i18n:en-*')->willReturn([$englishProperty1->reveal()]);

        $copyEvent->getCopiedNode()->willReturn($copiedNode->reveal());

        $webspace = $this->prophesize(Webspace::class);
        $localization1 = $this->prophesize(Localization::class);
        $localization1->getLocale()->willReturn('en');
        $webspace->getAllLocalizations()->willReturn([$localization1]);
        $this->webspaceManager->findWebspaceByKey('test_io')->willReturn($webspace->reveal());

        $this->subscriber->deleteUnavailableLocales($copyEvent->reveal());

        $frenchProperty1->remove()->shouldBeCalled();
        $frenchProperty2->remove()->shouldBeCalled();
        $germanProperty1->remove()->shouldBeCalled();
        $germanProperty2->remove()->shouldBeCalled();
        $englishProperty1->remove()->shouldNotBeCalled();
    }
}
