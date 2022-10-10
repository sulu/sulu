<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Massive\Bundle\SearchBundle\Search\Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\QueryHit;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Search\EventListener\HitListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class HitListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var HitListener
     */
    private $listener;

    /**
     * @var ObjectProphecy<Document>
     */
    private $document;

    /**
     * @var ObjectProphecy<HitEvent>
     */
    private $event;

    protected function setUp(): void
    {
        $reflection = $this->prophesize(\ReflectionClass::class);
        $reflection->isSubclassOf(BasePageDocument::class)->willReturn(true);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->reveal()->reflection = $reflection->reveal();

        $this->document = $this->prophesize(Document::class);

        $hit = $this->prophesize(QueryHit::class);
        $hit->getDocument()->willReturn($this->document->reveal());

        $this->event = $this->prophesize(HitEvent::class);
        $this->event->getMetadata()->willReturn($metadata->reveal());
        $this->event->getHit()->willReturn($hit->reveal());

        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->listener = new HitListener($this->requestAnalyzer->reveal());
    }

    public function testOnHit(): void
    {
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/en');

        $this->document->getUrl()->willReturn('/test');
        $this->document->setUrl('/en/test')->shouldBeCalled();

        $this->listener->onHit($this->event->reveal());
    }

    public function testOnHitNoUrl(): void
    {
        $this->document->getUrl()->willReturn(null);
        $this->document->setUrl(Argument::any())->shouldNotBeCalled();

        $this->listener->onHit($this->event->reveal());
    }

    public function testOnHitAbsolute(): void
    {
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/en');

        $this->document->getUrl()->willReturn('http://www.google.at');
        $this->document->setUrl(Argument::any())->shouldNotBeCalled();

        $this->listener->onHit($this->event->reveal());
    }
}
