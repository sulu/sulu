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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Form\DataTransformer\DocumentToUuidTransformer;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DocumentToUuidTransformerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<UuidBehavior>
     */
    private $document;

    private $transformer;

    public function setUp(): void
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->document = $this->prophesize(UuidBehavior::class);

        $this->transformer = new DocumentToUuidTransformer($this->documentManager->reveal());
    }

    /**
     * It should transform a document to a UUID.
     */
    public function testTransform(): void
    {
        $this->document->getUuid()->willReturn('1234');

        $this->assertEquals('1234', $this->transformer->transform($this->document->reveal()));
    }

    /**
     * It should throw an exception if reverse transform is attempted with something
     * that is not a UUID.
     */
    public function testReverseTransformNotUuid(): void
    {
        $this->expectExceptionMessage('Given UUID is not a UUID');
        $this->expectException(TransformationFailedException::class);
        $this->transformer->reverseTransform(1234);
    }

    /**
     * It should throw an exception if the document was not found.
     */
    public function testReverseTransformNotFound(): void
    {
        $uuid = '9fce0181-fabf-43d5-9b73-79f100ce2a9b';
        $exceptionMessage = \sprintf('No document has been set for the findEvent for "%s".', $uuid);

        $this->expectExceptionMessage($exceptionMessage);
        $this->expectException(DocumentManagerException::class);
        $this->documentManager->find($uuid)->willThrow(new DocumentManagerException($exceptionMessage));
        $this->transformer->reverseTransform($uuid);
    }
}
