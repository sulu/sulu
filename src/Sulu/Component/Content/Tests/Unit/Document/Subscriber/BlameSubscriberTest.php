<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use stdClass;
use Sulu\Component\Content\Document\Behavior\LocalizedBlameBehavior;
use Sulu\Component\Content\Document\Subscriber\BlameSubscriber;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BlameSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var HydrateEvent
     */
    private $hydrateEvent;

    /**
     * @var stdClass
     */
    private $notImplementing;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var DocumentAccessor
     */
    private $accessor;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var AnonymousToken
     */
    private $anonymousToken;

    /**
     * @var stdClass
     */
    private $notUser;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var LocalizedBlameTestDocument
     */
    private $document;

    /**
     * @var BlameSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->notImplementing = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->accessor = $this->prophesize(DocumentAccessor::class);
        $this->user = $this->prophesize(UserInterface::class);
        $this->anonymousToken = $this->prophesize(AnonymousToken::class);
        $this->notUser = new \stdClass();
        $this->token = $this->prophesize(TokenInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorage::class);
        $this->document = new LocalizedBlameTestDocument();

        $this->subscriber = new BlameSubscriber(
            $this->tokenStorage->reveal()
        );

        $this->persistEvent->getNode()->willReturn($this->node);
        $this->persistEvent->getOptions()->willReturn([]);
        $this->persistEvent->getAccessor()->willReturn($this->accessor);
    }

    /**
     * It should return early if no token storage is provided.
     */
    public function testNoTokenStorage()
    {
    }

    /**
     * It should return early if the document is not implementing the behavior.
     */
    public function testPersistNotImplementing()
    {
        $this->persistEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early if the token is null.
     */
    public function testPersistTokenIsNull()
    {
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->tokenStorage->getToken()->willReturn(null);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early if the locale is null.
     */
    public function testPersistLocaleIsNull()
    {
        $this->persistEvent->getLocale()->willReturn(null);
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->node->setProperty()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should return early if the token is AnonymousToken.
     */
    public function testPersistTokenIsAnonymous()
    {
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->tokenStorage->getToken()->willReturn($this->anonymousToken->reveal());

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should throw an exception if the token is not a Sulu User.
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
     * It should assign "creator" if there is creator is actually null.
     */
    public function testPersistCreatorWhenNull()
    {
        $locale = 'fr';
        $document = new LocalizedBlameTestDocument();

        $this->persistEvent->getLocale()->willReturn($locale);
        $this->persistEvent->getDocument()->willReturn($document);

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->user->getId()->willReturn(2);

        $this->accessor->set('creator', 2)->shouldBeCalled();
        $this->accessor->set('changer', 2)->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should always assign "changer".
     */
    public function testPersistChanger()
    {
        $locale = 'fr';
        $document = new LocalizedBlameTestDocument($this->user->reveal());

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->user->getId()->willReturn(2);

        $this->persistEvent->getLocale()->willReturn($locale);
        $this->persistEvent->getDocument()->willReturn($document);
        $this->accessor->set('changer', 2)->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }
}

class LocalizedBlameTestDocument implements LocalizedBlameBehavior
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
