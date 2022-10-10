<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Listener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Listener\SwiftMailerListener;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SwiftMailerListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MarkupParserInterface>
     */
    private $markupParser;

    /**
     * @var ObjectProphecy<\Swift_Events_SendEvent>
     */
    private $event;

    /**
     * @var ObjectProphecy<\Swift_Mime_SimpleMessage>
     */
    private $simpleMessage;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var SwiftMailerListener
     */
    private $listener;

    protected function setUp(): void
    {
        if (!\class_exists(\Swift_Mailer::class)) {
            $this->markTestSkipped('Require "swiftmailer" to be installed.');
        }

        $this->markupParser = $this->prophesize(MarkupParserInterface::class);
        $this->event = $this->prophesize(\Swift_Events_SendEvent::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->simpleMessage = $this->prophesize(\Swift_Mime_SimpleMessage::class);
        $this->event->getMessage()->willReturn($this->simpleMessage->reveal());

        $this->defaultLocale = 'en';
        $traverseable = new \ArrayIterator(['html' => $this->markupParser->reveal()]);

        $this->listener = new SwiftMailerListener(
            $traverseable,
            $this->requestStack->reveal(),
            $this->defaultLocale
        );
    }

    public function testReplaceMarkup(): void
    {
        /** @var Request|ObjectProphecy $request */
        $request = $this->prophesize(Request::class);
        $request->getLocale()->willReturn('de');
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $this->simpleMessage->getBodyContentType()->willReturn('text/html');
        $this->simpleMessage->getBody()->willReturn('<html><sulu-link href="123-123-123"/></html>');

        $this->markupParser->parse('<html><sulu-link href="123-123-123"/></html>', 'de')
            ->willReturn('<html><a href="/test">Page-Title</a></html>')->shouldBeCalled();

        $this->simpleMessage->setBody('<html><a href="/test">Page-Title</a></html>')->shouldBeCalled();

        $this->listener->beforeSendPerformed($this->event->reveal());
    }

    public function testReplaceMarkupWithoutRequest(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $this->simpleMessage->getBodyContentType()->willReturn('text/html');
        $this->simpleMessage->getBody()->willReturn('<html><sulu-link href="123-123-123"/></html>');

        $this->markupParser->parse('<html><sulu-link href="123-123-123"/></html>', $this->defaultLocale)
            ->willReturn('<html><a href="/test">Page-Title</a></html>')->shouldBeCalled();

        $this->simpleMessage->setBody('<html><a href="/test">Page-Title</a></html>')->shouldBeCalled();

        $this->listener->beforeSendPerformed($this->event->reveal());
    }

    public function testReplaceMarkupWithUnknownContentType(): void
    {
        /** @var Request|ObjectProphecy $request */
        $request = $this->prophesize(Request::class);
        $request->getLocale()->willReturn('de');
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $this->simpleMessage->getBodyContentType()->willReturn('text/plain');
        $this->simpleMessage->getBody()->willReturn('<html><sulu-link href="123-123-123"/></html>');

        $this->markupParser->parse('<html><sulu-link href="123-123-123"/></html>', 'de')
            ->shouldNotBeCalled();

        $this->simpleMessage->setBody('<html><a href="/test">Page-Title</a></html>')->shouldNotBeCalled();

        $this->listener->beforeSendPerformed($this->event->reveal());
    }
}
