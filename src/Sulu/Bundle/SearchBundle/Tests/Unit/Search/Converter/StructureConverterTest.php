<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Unit\Search\Converter;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Field;
use Massive\Bundle\SearchBundle\Search\Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\FieldEvaluator;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Massive\Bundle\SearchBundle\Search\ObjectToDocumentConverter;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\SearchBundle\Search\Converter\StructureConverter;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StructureConverterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<SearchManagerInterface>
     */
    private $searchManager;

    /**
     * @var ObjectProphecy<ObjectToDocumentConverter>
     */
    private $objectToDocumentConverter;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $eventDispatcher;

    /**
     * @var StructureConverter
     */
    private $structureConverter;

    protected function setUp(): void
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->objectToDocumentConverter = $this->prophesize(ObjectToDocumentConverter::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->structureConverter = new StructureConverter(
            $this->documentManager->reveal(),
            $this->searchManager->reveal(),
            $this->objectToDocumentConverter->reveal(),
            $this->eventDispatcher->reveal()
        );
    }

    public function testConvertWithoutDocument(): void
    {
        $this->assertSame('abcd', $this->structureConverter->convert('abcd'));
    }

    public function testConvertNull(): void
    {
        $this->assertNull($this->structureConverter->convert(null));
    }

    public function testConvertStringValue(): void
    {
        $uuid = 'abcd';
        $locale = 'en';

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn($locale);

        $object = $this->prophesize(BasePageDocument::class);
        $this->documentManager->find($uuid, $locale)->willReturn($object->reveal());

        $indexMetadata = $this->prophesize(ClassMetadata::class);
        $this->searchManager->getMetadata($object->reveal())->willReturn($indexMetadata->reveal());

        $defaultIndexMetadata = $this->prophesize(IndexMetadata::class);
        $indexMetadata->getIndexMetadata('_default')->willReturn($defaultIndexMetadata->reveal());

        $objectDocument = $this->prophesize(Document::class);
        $this->objectToDocumentConverter->objectToDocument(
            $defaultIndexMetadata->reveal(),
            $object->reveal()
        )->willReturn($objectDocument->reveal());

        $fieldEvaluator = $this->prophesize(FieldEvaluator::class);
        $this->objectToDocumentConverter->getFieldEvaluator()->willReturn($fieldEvaluator->reveal());

        $this->eventDispatcher->dispatch(Argument::cetera(), SearchEvents::PRE_INDEX)->shouldBeCalled();

        $fields = [
            new Field('foo', 'abc'),
            new Field('bar', 'xyz'),
        ];
        $objectDocument->getFields()->willReturn($fields);

        $value = $this->structureConverter->convert($uuid, $document->reveal());

        $this->assertSame([
            'value' => $uuid,
            'fields' => $fields,
        ], $value);
    }

    public function testConvertStringValueNotFound(): void
    {
        $uuid = 'not-existing-value';
        $locale = 'en';

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn($locale);

        $this->documentManager->find($uuid, $locale)->willThrow(new DocumentNotFoundException());

        $value = $this->structureConverter->convert($uuid, $document->reveal());

        $this->assertSame([
            'value' => $uuid,
            'fields' => [],
        ], $value);
    }

    public function testConvertArrayValue(): void
    {
        $uuids = ['abcd', 'efgh', ['invalid-value']];
        $locale = 'en';

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn($locale);

        $firstObject = $this->prophesize(BasePageDocument::class);
        $secondObject = $this->prophesize(BasePageDocument::class);
        $this->documentManager->find($uuids[0], $locale)->willReturn($firstObject->reveal());
        $this->documentManager->find($uuids[1], $locale)->willReturn($secondObject->reveal());

        $firstIndexMetadata = $this->prophesize(ClassMetadata::class);
        $secondIndexMetadata = $this->prophesize(ClassMetadata::class);
        $this->searchManager->getMetadata($firstObject->reveal())->willReturn($firstIndexMetadata->reveal());
        $this->searchManager->getMetadata($secondObject->reveal())->willReturn($secondIndexMetadata->reveal());

        $firstDefaultIndexMetadata = $this->prophesize(IndexMetadata::class);
        $secondDefaultIndexMetadata = $this->prophesize(IndexMetadata::class);
        $firstIndexMetadata->getIndexMetadata('_default')->willReturn($firstDefaultIndexMetadata->reveal());
        $secondIndexMetadata->getIndexMetadata('_default')->willReturn($secondDefaultIndexMetadata->reveal());

        $firstObjectDocument = $this->prophesize(Document::class);
        $secondObjectDocument = $this->prophesize(Document::class);
        $this->objectToDocumentConverter->objectToDocument(
            $firstDefaultIndexMetadata->reveal(),
            $firstObject->reveal()
        )->willReturn($firstObjectDocument->reveal());
        $this->objectToDocumentConverter->objectToDocument(
            $secondDefaultIndexMetadata->reveal(),
            $secondObject->reveal()
        )->willReturn($secondObjectDocument->reveal());

        $fieldEvaluator = $this->prophesize(FieldEvaluator::class);
        $this->objectToDocumentConverter->getFieldEvaluator()->willReturn($fieldEvaluator->reveal());

        $this->eventDispatcher->dispatch(Argument::cetera(), SearchEvents::PRE_INDEX)->shouldBeCalledTimes(2);

        $firstFields = [
            new Field('foo', 'abc'),
            new Field('bar', 'xyz'),
        ];
        $secondFields = [
            new Field('hello', 'world'),
        ];
        $firstObjectDocument->getFields()->willReturn($firstFields);
        $secondObjectDocument->getFields()->willReturn($secondFields);

        $value = $this->structureConverter->convert($uuids, $document->reveal());

        $this->assertArrayHasKey('value', $value);
        $this->assertSame($uuids, $value['value']);

        $this->assertArrayHasKey('fields', $value);
        $this->assertCount(3, $value['fields']);

        $this->assertSame('0#' . $firstFields[0]->getName(), $value['fields'][0]->getName());
        $this->assertSame($firstFields[0]->getValue(), $value['fields'][0]->getValue());

        $this->assertSame('0#' . $firstFields[1]->getName(), $value['fields'][1]->getName());
        $this->assertSame($firstFields[1]->getValue(), $value['fields'][1]->getValue());

        $this->assertSame('1#' . $secondFields[0]->getName(), $value['fields'][2]->getName());
        $this->assertSame($secondFields[0]->getValue(), $value['fields'][2]->getValue());
    }

    public function testConvertArrayValueNotFound(): void
    {
        $uuids = ['abcd', 'not-existing-value'];
        $locale = 'en';

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn($locale);

        $object = $this->prophesize(BasePageDocument::class);
        $this->documentManager->find($uuids[0], $locale)->willReturn($object->reveal());
        $this->documentManager->find($uuids[1], $locale)->willThrow(new DocumentNotFoundException());

        $indexMetadata = $this->prophesize(ClassMetadata::class);
        $this->searchManager->getMetadata($object->reveal())->willReturn($indexMetadata->reveal());

        $defaultIndexMetadata = $this->prophesize(IndexMetadata::class);
        $indexMetadata->getIndexMetadata('_default')->willReturn($defaultIndexMetadata->reveal());

        $objectDocument = $this->prophesize(Document::class);
        $this->objectToDocumentConverter->objectToDocument(
            $defaultIndexMetadata->reveal(),
            $object->reveal()
        )->willReturn($objectDocument->reveal());

        $fieldEvaluator = $this->prophesize(FieldEvaluator::class);
        $this->objectToDocumentConverter->getFieldEvaluator()->willReturn($fieldEvaluator->reveal());

        $this->eventDispatcher->dispatch(Argument::cetera(), SearchEvents::PRE_INDEX)->shouldBeCalledTimes(1);

        $fields = [
            new Field('foo', 'abc'),
            new Field('bar', 'xyz'),
        ];
        $objectDocument->getFields()->willReturn($fields);

        $value = $this->structureConverter->convert($uuids, $document->reveal());

        $this->assertArrayHasKey('value', $value);
        $this->assertSame($uuids, $value['value']);

        $this->assertArrayHasKey('fields', $value);
        $this->assertCount(2, $value['fields']);

        $this->assertSame('0#' . $fields[0]->getName(), $value['fields'][0]->getName());
        $this->assertSame($fields[0]->getValue(), $value['fields'][0]->getValue());

        $this->assertSame('0#' . $fields[1]->getName(), $value['fields'][1]->getName());
        $this->assertSame($fields[1]->getValue(), $value['fields'][1]->getValue());
    }
}
