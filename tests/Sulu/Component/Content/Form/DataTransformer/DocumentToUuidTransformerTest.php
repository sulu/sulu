<?php

namespace Sulu\Component\Content\Form\DataTransformer;

use Prophecy\PhpUnit\ProphecyTestCase;
use Doctrine\ODM\PHPCR\DocumentManager;
use Sulu\Component\Content\Form\DataTransformer\DocumentToUuidTransformer;

class DocumentToUuidTransformerTest extends \PHPUnit_Framework_TestCase
{
    private $documentManager;
    private $node;
    private $document;

    public function setUp()
    {
        $this->documentManager = $this->prophesize('Doctrine\ODM\PHPCR\DocumentManager');
        $this->node = $this->prophesize('PHPCR\NodeInterface');
        $this->document = new \stdClass;

        $this->transformer = new DocumentToUuidTransformer($this->documentManager->reveal());
    }

    public function testTransform()
    {
        $this->documentManager->getNodeForDocument($this->document)->willReturn($this->node);
        $this->node->getIdentifier()->willReturn('1234');

        $this->assertEquals(1234, $this->transformer->transform($this->document));
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Given UUID is not a UUID
     */
    public function testReverseTransformNotUuid()
    {
        $this->transformer->reverseTransform(1234);
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Could not find document
     */
    public function testReverseTransformNotFound()
    {
        $uuid = '9fce0181-fabf-43d5-9b73-79f100ce2a9b';
        $this->documentManager->find(null, $uuid)->willReturn(null);
        $this->transformer->reverseTransform($uuid);
    }
}
