<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Listener;

use Sulu\Bundle\MarkupBundle\Listener\MarkupListener;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class MarkupListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MarkupParserInterface
     */
    private $markupParser;

    /**
     * @var FilterResponseEvent
     */
    private $event;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var HeaderBag
     */
    private $responseHeaders;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var MarkupListener
     */
    private $listener;

    protected function setUp()
    {
        $this->markupParser = $this->prophesize(MarkupParserInterface::class);
        $this->event = $this->prophesize(FilterResponseEvent::class);

        $this->request = $this->prophesize(Request::class);
        $this->response = $this->prophesize(Response::class);

        $this->responseHeaders = $this->prophesize(HeaderBag::class);
        $this->response->reveal()->headers = $this->responseHeaders->reveal();

        $this->event->getRequest()->willReturn($this->request->reveal());
        $this->event->getResponse()->willReturn($this->response->reveal());

        $this->listener = new MarkupListener(['html' => $this->markupParser->reveal()], ['text/html' => 'html']);
    }

    public function testReplaceMarkup()
    {
        $this->request->getRequestFormat(null)->willReturn('html');
        $this->request->getLocale()->willReturn('de');
        $this->response->getContent()->willReturn('<html><sulu:link href="123-123-123"/></html>');

        $this->markupParser->parse('<html><sulu:link href="123-123-123"/></html>', 'de')
            ->willReturn('<html><a href="/test">Page-Title</a></html>')->shouldBeCalled();

        $this->response->setContent('<html><a href="/test">Page-Title</a></html>')->shouldBeCalled();

        $this->listener->replaceMarkup($this->event->reveal());
    }
}
