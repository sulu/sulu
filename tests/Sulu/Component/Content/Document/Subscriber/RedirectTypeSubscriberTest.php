<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\ProxyFactory;

class RedirectTypeSubscriberTest extends SubscriberTestCase
{
    /**
     * @var RedirectTypeSubscriber
     */
    private $subscriber;

    /**
     * @var RedirectTypeBehavior
     */
    private $document;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    public function setUp()
    {
        parent::setUp();

        $this->proxyFactory = $this->prophesize(ProxyFactory::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);

        $this->encoder->localizedSystemName('nodeType', 'en')->willReturn('i18n:en-nodeType');
        $this->encoder->localizedSystemName('external', 'en')->willReturn('i18n:en-external');
        $this->encoder->localizedSystemName('internal_link', 'en')->willReturn('i18n:en-internal_link');

        $this->subscriber = new RedirectTypeSubscriber(
            $this->encoder->reveal(),
            $this->proxyFactory->reveal(),
            $this->documentRegistry->reveal()
        );

        $this->document = $this->prophesize(RedirectTypeBehavior::class);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
    }

    public function testPersistLocaleIsNull()
    {
        $this->persistEvent->getLocale()->willReturn(null);
        $this->node->setProperty()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistExternalRedirect()
    {
        $this->persistEvent->getLocale()->willReturn('en');

        $this->document->getRedirectType()->willReturn(RedirectType::EXTERNAL);
        $this->document->getRedirectExternal()->willReturn('http://www.example.org');
        $this->document->getRedirectTarget()->willReturn(null);

        $this->node->setProperty('i18n:en-nodeType', RedirectType::EXTERNAL, PropertyType::LONG)->shouldBeCalled();
        $this->node->setProperty('i18n:en-external', 'http://www.example.org')->shouldBeCalled();
        $this->node->setProperty('i18n:en-internal_link', Argument::any())->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistInternalRedirect()
    {
        $linkedDocument = new \stdClass();
        $linkedNode = $this->prophesize(NodeInterface::class);

        $this->documentRegistry->getNodeForDocument($linkedDocument)->willReturn($linkedNode);
        $this->persistEvent->getLocale()->willReturn('en');

        $this->document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $this->document->getRedirectExternal()->willReturn(null);
        $this->document->getRedirectTarget()->willReturn($linkedDocument);

        $this->node->setProperty('i18n:en-nodeType', RedirectType::INTERNAL, PropertyType::LONG)->shouldBeCalled();
        $this->node->setProperty('i18n:en-external', null)->shouldBeCalled();
        $this->node->setProperty('i18n:en-internal_link', $linkedNode)->shouldBeCalled();

        // required because hydrating is also tested
        $this->node->getPropertyValueWithDefault('i18n:en-nodeType', RedirectType::NONE)
            ->willReturn(RedirectType::INTERNAL);
        $this->node->getPropertyValueWithDefault('i18n:en-internal_link', null)->willReturn($linkedNode);
        $this->proxyFactory->createProxyForNode($this->document, $linkedNode)->willReturn($linkedNode);
        $this->document->setRedirectType(RedirectType::INTERNAL)->shouldBeCalled();
        $this->document->setRedirectTarget($linkedNode)->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHydrateExternalRedirect()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->hydrateEvent->getNode()->willReturn($this->node);
        $this->hydrateEvent->getLocale()->willReturn('en');

        $this->node->getPropertyValueWithDefault('i18n:en-nodeType', RedirectType::NONE)
            ->willReturn(RedirectType::EXTERNAL);
        $this->node->getPropertyValueWithDefault('i18n:en-external', null)->willReturn('http://www.example.org');

        $this->document->setRedirectType(RedirectType::EXTERNAL)->shouldBeCalled();
        $this->document->setRedirectExternal('http://www.example.org')->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
