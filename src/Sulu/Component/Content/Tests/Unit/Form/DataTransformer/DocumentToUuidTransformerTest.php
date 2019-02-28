<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Form\DataTransformer;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Form\DataTransformer\DocumentToUuidTransformer;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DocumentToUuidTransformerTest extends TestCase
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
     */
    public function testReverseTransformNotUuid()
    {
        $this->expectExceptionMessage('Given UUID is not a UUID');
        $this->expectException(TransformationFailedException::class);
        $this->transformer->reverseTransform(1234);
    }

    public function testReverseTransformNotFound()
    {
        $this->expectExceptionMessage('Could not find document');
        $this->expectException(TransformationFailedException::class);
        $uuid = '9fce0181-fabf-43d5-9b73-79f100ce2a9b';
        $this->documentManager->find($uuid)->willReturn(null);
        $this->transformer->reverseTransform($uuid);
    }
}
