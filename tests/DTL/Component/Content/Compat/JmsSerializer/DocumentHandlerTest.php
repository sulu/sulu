<?php

namespace DTL\Component\Content\Compat\JmsSerializer;

use JMS\Serializer\JsonSerializationVisitor;
use DTL\Component\Content\Document\DocumentInterface;
use JMS\Serializer\Context;

class DocumentHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->visitor = $this->prophesize(JsonSerializationVisitor::class);
        $this->document = $this->prophesize(DocumentInterface::class);
        $this->context = $this->prophesize(Context::class);
        $this->handler = new DocumentHandler();
    }

    public function testHandleDocument()
    {
        $this->document->getUuid()->willReturn('1234');
        $result = $this->handler->handleDocument(
            $this->visitor->reveal(),
            $this->document->reveal(),
            array(),
            $this->context->reveal()
        );

        $this->assertEquals('1234', $result);
    }
}
