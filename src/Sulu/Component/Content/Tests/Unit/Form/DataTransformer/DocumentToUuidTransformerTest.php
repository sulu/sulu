<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Form\DataTransformer;

use Sulu\Component\Content\Form\DataTransformer\DocumentToUuidTransformer;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManager;

class DocumentToUuidTransformerTest extends \PHPUnit_Framework_TestCase
{
    private $documentManager;
    private $node;
    private $document;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->node = $this->prophesize('PHPCR\NodeInterface');
        $this->document = $this->prophesize(UuidBehavior::class);

        $this->transformer = new DocumentToUuidTransformer($this->documentManager->reveal());
    }

    /**
     * It should transform a document to a UUID.
     */
    public function testTransform()
    {
        $this->document->getUuid()->willReturn('1234');

        $this->assertEquals('1234', $this->transformer->transform($this->document->reveal()));
    }

    /**
     * It should throw an exception if reverse transform is attempted with something
     * that is not a UUID.
     *
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
        $this->documentManager->find($uuid)->willReturn(null);
        $this->transformer->reverseTransform($uuid);
    }
}
