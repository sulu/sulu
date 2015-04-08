<?php

namespace DTL\Component\Content\PhpcrOdm\EventSubscriber;

use Prophecy\PhpUnit\ProphecyTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use DTL\Component\Content\Document\DocumentInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Prophecy\Argument;
use Sulu\Component\Security\Authentication\UserInterface;

class BlameSubscriberTest extends ProphecyTestCase
{
    const USER_ID = 5;

    private $tokenStorage;
    private $token;
    private $subscriber;
    private $event;
    private $document;

    public function setUp()
    {
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(UserInterface::class);
        $this->event = $this->prophesize(LifecycleEventArgs::class);
        $this->document = $this->prophesize(DocumentInterface::class);

        $this->subscriber = new BlameSubscriber($this->tokenStorage->reveal());

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->user->getId()->willReturn(self::USER_ID);
    }

    public function testNotDocument()
    {
        $this->document->setChanger(Argument::any())->shouldNotBeCalled();
        $this->document->setCreator(Argument::any())->shouldNotBeCalled();

        $this->event->getObject()->willReturn(new \stdClass);
        $this->subscriber->prePersist($this->event->reveal());
    }

    public function testNotAuthenticated()
    {
        $this->document->setChanger(Argument::any())->shouldNotBeCalled();
        $this->document->setCreator(Argument::any())->shouldNotBeCalled();

        $this->event->getObject()->willReturn($this->document->reveal());
        $this->token->isAuthenticated()->willReturn(false);
        $this->subscriber->prePersist($this->event->reveal());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Expected user object to be instance of
     */
    public function testNotUserObject()
    {
        $this->token->getUser()->willReturn('foo');
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->token->isAuthenticated()->willReturn(true);
        $this->subscriber->prePersist($this->event->reveal());
    }

    public function providePrePersistExistingCreator()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @dataProvider providePrePersistExistingCreator
     */
    public function testPrePersistExistingCreator($existing)
    {
        $this->token->getUser()->willReturn($this->user);
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->token->isAuthenticated()->willReturn(true);

        if ($existing) {
            $this->document->getCreator()->willReturn(6);
            $this->document->setCreator(Argument::any())->shouldNotBeCalled();
        } else {
            $this->document->getCreator()->willReturn(null);
            $this->document->setCreator(self::USER_ID)->shouldBeCalled();
        }
        $this->document->setChanger(self::USER_ID)->shouldBeCalled();
        $this->subscriber->prePersist($this->event->reveal());
    }

    public function testPreUpdate()
    {
        $this->token->getUser()->willReturn($this->user);
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->token->isAuthenticated()->willReturn(true);

        $this->document->getCreator()->shouldNotBeCalled();
        $this->document->setCreator(self::USER_ID)->shouldNotBeCalled();
        $this->document->setChanger(self::USER_ID)->shouldBeCalled();

        $this->subscriber->preUpdate($this->event->reveal());
    }
}
