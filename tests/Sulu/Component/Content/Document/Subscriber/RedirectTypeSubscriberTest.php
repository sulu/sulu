<?php
/*
 * This file is part of Sulu.
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
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Exception\MandatoryPropertyException;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\ProxyFactory;

class RedirectTypeSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testPersistWithTypeNone()
    {
        $locale = 'en';

        $encoder = $this->prophesize(PropertyEncoder::class);
        $factory = $this->prophesize(ProxyFactory::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $subscriber = new RedirectTypeSubscriber($encoder->reveal(), $factory->reveal(), $registry->reveal());

        $event = $this->prophesize(PersistEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(PageDocument::class);
        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn($locale);

        $encoder->localizedSystemName(RedirectTypeSubscriber::REDIRECT_TYPE_FIELD, $locale)
            ->willReturn('i18n:en-nodeType');
        $encoder->localizedSystemName(RedirectTypeSubscriber::EXTERNAL_FIELD, $locale)
            ->willReturn('i18n:en-external');
        $encoder->localizedSystemName(RedirectTypeSubscriber::INTERNAL_FIELD, $locale)
            ->willReturn('i18n:en-internal_link');

        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getRedirectExternal()->willReturn(null);
        $document->getRedirectTarget()->willReturn(null);
        $document->setRedirectType(Argument::any())->shouldNotBeCalled();
        $document->setRedirectExternal(Argument::any())->shouldNotBeCalled();
        $document->setRedirectTarget(Argument::any())->shouldNotBeCalled();

        $node->setProperty('i18n:en-nodeType', RedirectType::NONE, PropertyType::LONG)->shouldBeCalled();
        $node->setProperty('i18n:en-external', null)->shouldBeCalled();

        $subscriber->doPersist($event->reveal());
    }

    public function testPersistWithTypeExternal()
    {
        $locale = 'en';

        $encoder = $this->prophesize(PropertyEncoder::class);
        $factory = $this->prophesize(ProxyFactory::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $subscriber = new RedirectTypeSubscriber($encoder->reveal(), $factory->reveal(), $registry->reveal());

        $event = $this->prophesize(PersistEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(PageDocument::class);
        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn($locale);

        $encoder->localizedSystemName(RedirectTypeSubscriber::REDIRECT_TYPE_FIELD, $locale)
            ->willReturn('i18n:en-nodeType');
        $encoder->localizedSystemName(RedirectTypeSubscriber::EXTERNAL_FIELD, $locale)
            ->willReturn('i18n:en-external');
        $encoder->localizedSystemName(RedirectTypeSubscriber::INTERNAL_FIELD, $locale)
            ->willReturn('i18n:en-internal_link');

        $document->getRedirectType()->willReturn(RedirectType::EXTERNAL);
        $document->getRedirectExternal()->willReturn('http://www.google.at');
        $document->getRedirectTarget()->willReturn(null);
        $document->setRedirectType(Argument::any())->shouldNotBeCalled();
        $document->setRedirectExternal(Argument::any())->shouldNotBeCalled();
        $document->setRedirectTarget(Argument::any())->shouldNotBeCalled();

        $node->setProperty('i18n:en-nodeType', RedirectType::EXTERNAL, PropertyType::LONG)->shouldBeCalled();
        $node->setProperty('i18n:en-external', 'http://www.google.at')->shouldBeCalled();

        $subscriber->doPersist($event->reveal());
    }

    public function testPersistWithTypeInternal()
    {
        $locale = 'en';

        $encoder = $this->prophesize(PropertyEncoder::class);
        $factory = $this->prophesize(ProxyFactory::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $subscriber = new RedirectTypeSubscriber($encoder->reveal(), $factory->reveal(), $registry->reveal());

        $event = $this->prophesize(PersistEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(PageDocument::class);
        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn($locale);

        $encoder->localizedSystemName(RedirectTypeSubscriber::REDIRECT_TYPE_FIELD, $locale)
            ->willReturn('i18n:en-nodeType');
        $encoder->localizedSystemName(RedirectTypeSubscriber::EXTERNAL_FIELD, $locale)
            ->willReturn('i18n:en-external');
        $encoder->localizedSystemName(RedirectTypeSubscriber::INTERNAL_FIELD, $locale)
            ->willReturn('i18n:en-internal_link');

        $redirectDocument = $this->prophesize(PageDocument::class);
        $redirectNode = $this->prophesize(NodeInterface::class);

        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectExternal()->willReturn(null);
        $document->getRedirectTarget()->willReturn($redirectDocument->reveal());
        $document->setRedirectType(RedirectType::INTERNAL)->shouldBeCalled();
        $document->setRedirectExternal(Argument::any())->shouldNotBeCalled();
        $document->setRedirectTarget($redirectDocument->reveal())->shouldBeCalled();

        $registry->getNodeForDocument($redirectDocument->reveal())->willReturn($redirectNode->reveal());
        $factory->createProxyForNode($document, $redirectNode->reveal())->willReturn($redirectDocument);

        $node->setProperty('i18n:en-nodeType', RedirectType::INTERNAL, PropertyType::LONG)->shouldBeCalled();
        $node->setProperty('i18n:en-external', null)->shouldBeCalled();
        $node->setProperty('i18n:en-internal_link', $redirectNode->reveal())->shouldBeCalled();
        $node->getPropertyValueWithDefault('i18n:en-nodeType', 1)->willReturn(RedirectType::INTERNAL);
        $node->getPropertyValueWithDefault('i18n:en-internal_link', null)->willReturn($redirectNode->reveal());

        $subscriber->doPersist($event->reveal());
    }

    public function testPersistWithTypeInternalMissingLink()
    {
        $this->setExpectedException(MandatoryPropertyException::class);

        $locale = 'en';

        $encoder = $this->prophesize(PropertyEncoder::class);
        $factory = $this->prophesize(ProxyFactory::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $subscriber = new RedirectTypeSubscriber($encoder->reveal(), $factory->reveal(), $registry->reveal());

        $event = $this->prophesize(PersistEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(PageDocument::class);
        $event->getNode()->willReturn($node->reveal());
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn($locale);

        $encoder->localizedSystemName(RedirectTypeSubscriber::REDIRECT_TYPE_FIELD, $locale)
            ->willReturn('i18n:en-nodeType');
        $encoder->localizedSystemName(RedirectTypeSubscriber::EXTERNAL_FIELD, $locale)
            ->willReturn('i18n:en-external');
        $encoder->localizedSystemName(RedirectTypeSubscriber::INTERNAL_FIELD, $locale)
            ->willReturn('i18n:en-internal_link');

        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectExternal()->willReturn(null);
        $document->getRedirectTarget()->willReturn(null);
        $document->setRedirectType(Argument::any())->shouldNotBeCalled();
        $document->setRedirectExternal(Argument::any())->shouldNotBeCalled();
        $document->setRedirectTarget(Argument::any())->shouldNotBeCalled();

        $node->setProperty('i18n:en-nodeType', RedirectType::INTERNAL, PropertyType::LONG)->shouldBeCalled();
        $node->setProperty('i18n:en-external', null)->shouldBeCalled();

        $subscriber->doPersist($event->reveal());
    }
}
