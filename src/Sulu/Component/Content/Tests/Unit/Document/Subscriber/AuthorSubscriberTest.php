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
use Prophecy\Argument;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Content\Document\Behavior\AuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Subscriber\AuthorSubscriber;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;

/**
 * Tests for author-subscriber.
 */
class AuthorSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var AuthorSubscriber
     */
    private $authorSubscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->authorSubscriber = new AuthorSubscriber(
            $this->propertyEncoder->reveal(),
            $this->userRepository->reveal()
        );
    }

    public function testSetAuthorOnDocument()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('sulu:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHOR_PROPERTY_NAME, 'de')
            ->willReturn('sulu:author');

        $node->getPropertyValueWithDefault('sulu:authored', null)->willReturn(new \DateTime('2017-01-01'));
        $node->getPropertyValueWithDefault('sulu:author', null)->willReturn(1);

        $document->setAuthored(new \DateTime('2017-01-01'))->shouldBeCalled();
        $document->setAuthor(1)->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnDocument($event->reveal());
    }

    public function testSetAuthorOnDocumentLocalized()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(LocalizedAuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system_localized', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authored');
        $this->propertyEncoder->encode('system_localized', AuthorSubscriber::AUTHOR_PROPERTY_NAME, 'de')
            ->willReturn('i18n:author');

        $node->getPropertyValueWithDefault('i18n:authored', null)->willReturn(new \DateTime('2017-01-01'));
        $node->getPropertyValueWithDefault('i18n:author', null)->willReturn(1);

        $document->setAuthored(new \DateTime('2017-01-01'))->shouldBeCalled();
        $document->setAuthor(1)->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnDocument($event->reveal());
    }

    public function testSetAuthorOnNode()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('sulu:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHOR_PROPERTY_NAME, 'de')
            ->willReturn('sulu:author');

        $document->getAuthor()->willReturn(1);
        $document->getAuthored()->willReturn(new \DateTime('2017-01-01'));

        $node->setProperty('sulu:author', 1)->shouldBeCalled();
        $node->setProperty('sulu:authored', new \DateTime('2017-01-01'))->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnNode($event->reveal());
    }

    public function testSetAuthorOnNodeLocalized()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHOR_PROPERTY_NAME, 'de')
            ->willReturn('i18n:author');

        $document->getAuthor()->willReturn(1);
        $document->getAuthored()->willReturn(new \DateTime('2017-01-01'));

        $node->setProperty('i18n:author', 1)->shouldBeCalled();
        $node->setProperty('i18n:authored', new \DateTime('2017-01-01'))->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnNode($event->reveal());
    }

    public function testSetAuthorOnNodeDefaultValue()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHOR_PROPERTY_NAME, 'de')
            ->willReturn('i18n:author');

        $contact = $this->prophesize(Contact::class);
        $contact->getId()->willReturn(5);

        $user = $this->prophesize(User::class);
        $user->getContact()->willReturn($contact->reveal());

        $this->userRepository->findUserById(1)->willReturn($user->reveal());

        $document->getCreator()->willReturn(1);
        $document->getAuthor()->willReturn(null);
        $document->setAuthor(5)->shouldBeCalled()->will(
            function ($arguments) use ($document) {
                $document->getAuthor()->willReturn($arguments[0]);
            }
        );

        $document->getAuthored()->willReturn(null);
        $document->setAuthored(Argument::type(\DateTime::class))->shouldBeCalled()->will(
            function ($arguments) use ($document) {
                $document->getAuthored()->willReturn($arguments[0]);
            }
        );

        $node->setProperty('i18n:author', 5)->shouldBeCalled();
        $node->setProperty('i18n:authored', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnNode($event->reveal());
    }

    public function testSetAuthorOnNodeDefaultValueNoCreator()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHOR_PROPERTY_NAME, 'de')
            ->willReturn('i18n:author');

        $this->userRepository->findUserById(Argument::any())->shouldNotBeCalled();

        $document->getCreator()->willReturn(null);
        $document->getAuthor()->willReturn(null);
        $document->setAuthor(Argument::any())->shouldNotBeCalled();

        $document->getAuthored()->willReturn(null);
        $document->setAuthored(Argument::type(\DateTime::class))->shouldBeCalled()->will(
            function ($arguments) use ($document) {
                $document->getAuthored()->willReturn($arguments[0]);
            }
        );

        $node->setProperty('i18n:author', null)->shouldBeCalled();
        $node->setProperty('i18n:authored', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnNode($event->reveal());
    }

    public function testSetAuthorOnNodeDefaultValueNoContact()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHOR_PROPERTY_NAME, 'de')
            ->willReturn('i18n:author');

        $user = $this->prophesize(User::class);
        $this->userRepository->findUserById(1)->willReturn($user->reveal());

        $document->getCreator()->willReturn(1);
        $document->getAuthor()->willReturn(null);
        $document->setAuthor(Argument::any())->shouldNotBeCalled();

        $document->getAuthored()->willReturn(null);
        $document->setAuthored(Argument::type(\DateTime::class))->shouldBeCalled()->will(
            function ($arguments) use ($document) {
                $document->getAuthored()->willReturn($arguments[0]);
            }
        );

        $node->setProperty('i18n:author', null)->shouldBeCalled();
        $node->setProperty('i18n:authored', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnNode($event->reveal());
    }
}
