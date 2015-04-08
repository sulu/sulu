<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior;

use Sulu\Component\DocumentManager\Subscriber\Behavior\BlameSubscriber;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\BlameBehavior;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class BlameSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->notImplementing = new \stdClass;
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->accessor = $this->prophesize(DocumentAccessor::class);
        $this->user = $this->prophesize(UserInterface::class);
        $this->anonymousToken = $this->prophesize(AnonymousToken::class);
        $this->notUser = new \stdClass;
        $this->token = $this->prophesize(TokenInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorage::class);
        $this->document = new BlameTestDocument();

        $this->subscriber = new BlameSubscriber(
            $this->encoder->reveal(),
            $this->tokenStorage->reveal()
        );

        $this->persistEvent->getNode()->willReturn($this->node);
    }

    /**
     * It should return early if the document is not implementing the behavior
     */
    public function testPersistNotImplementing()
    {
        $this->persistEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early if the token is null
     */
    public function testPersistTokenIsNull()
    {
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->tokenStorage->getToken()->willReturn(null);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early if the token is AnonymousToken
     */
    public function testPersistTokenIsAnonymous()
    {
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->tokenStorage->getToken()->willReturn($this->anonymousToken->reveal());

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should throw an exception if the token is not a Sulu User
     *
     * @expectedException InvalidArgumentException
     */
    public function testPersistUserNotSuluUser()
    {
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->notUser);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should assign "creator" if there is creator is actually null
     */
    public function testPersistCreatorWhenNull()
    {
        $locale = 'fr';
        $document = new BlameTestDocument();

        $this->persistEvent->getLocale()->willReturn($locale);
        $this->persistEvent->getDocument()->willReturn($document);

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->user->getId()->willReturn(2);

        $this->encoder->localizedSystemName('creator', $locale)->willReturn('prop:creator');
        $this->encoder->localizedSystemName('changer', $locale)->willReturn('prop:changer');
        $this->node->setProperty('prop:creator', 2)->shouldBeCalled();
        $this->node->setProperty('prop:changer', 2)->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should always assign "changer"
     */
    public function testPersistChanger()
    {
        $locale = 'fr';
        $document = new BlameTestDocument($this->user->reveal());

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->user->getId()->willReturn(2);

        $this->persistEvent->getLocale()->willReturn($locale);
        $this->persistEvent->getDocument()->willReturn($document);
        $this->encoder->localizedSystemName('changer', $locale)->willReturn('prop:changer');
        $this->node->setProperty('prop:changer', 2)->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early when not implementing
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should set the creator and updated fields on the document
     */
    public function testHydrate()
    {
        $locale = 'fr';
        $document = new BlameTestDocument($this->user->reveal());

        $this->hydrateEvent->getLocale()->willReturn($locale);
        $this->hydrateEvent->getDocument()->willReturn($document);
        $this->hydrateEvent->getAccessor()->willReturn($this->accessor);
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->encoder->localizedSystemName('creator', $locale)->willReturn('prop:creator');
        $this->encoder->localizedSystemName('changer', $locale)->willReturn('prop:changer');
        $this->node->getPropertyValueWithDefault('prop:creator', null)->willReturn(1);
        $this->node->getPropertyValueWithDefault('prop:changer', null)->willReturn(2);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());

        $this->accessor->set('creator', 1);
        $this->accessor->set('changer', 2);
    }

}

class BlameTestDocument implements BlameBehavior
{
    private $creator;
    private $changer;

    public function __construct(UserInterface $creator = null, UserInterface $changer = null)
    {
        $this->creator = $creator;
        $this->changer = $changer;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getChanger()
    {
        return $this->changer;
    }
}
