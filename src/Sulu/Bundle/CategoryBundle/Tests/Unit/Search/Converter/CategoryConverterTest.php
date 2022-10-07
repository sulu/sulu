<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Search\Converter;

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
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Search\Converter\CategoryConverter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CategoryConverterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CategoryManagerInterface>
     */
    private $categoryManager;

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
     * @var CategoryConverter
     */
    private $categoryConverter;

    protected function setUp(): void
    {
        $this->categoryManager = $this->prophesize(CategoryManagerInterface::class);
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->objectToDocumentConverter = $this->prophesize(ObjectToDocumentConverter::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->categoryConverter = new CategoryConverter(
            $this->categoryManager->reveal(),
            $this->searchManager->reveal(),
            $this->objectToDocumentConverter->reveal(),
            $this->eventDispatcher->reveal()
        );
    }

    public function testConvertWithoutDocument(): void
    {
        $this->assertSame(1, $this->categoryConverter->convert(1));
    }

    public function testConvertNull(): void
    {
        $this->assertNull($this->categoryConverter->convert(null));
    }

    public function testConvertStringValue(): void
    {
        $id = 1;
        $locale = 'en';

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn($locale);

        $category = $this->prophesize(Category::class);
        $object = $this->prophesize(CategoryTranslation::class);
        $this->categoryManager->findById($id)->willReturn($category->reveal());
        $category->findTranslationByLocale($locale)->willReturn($object->reveal());

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

        $value = $this->categoryConverter->convert($id, $document->reveal());

        $this->assertSame([
            'value' => $id,
            'fields' => $fields,
        ], $value);
    }

    public function testConvertArrayValue(): void
    {
        $ids = [1, 2, 'invalid-value'];
        $locale = 'en';

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn($locale);

        $firstCategory = $this->prophesize(Category::class);
        $firstObject = $this->prophesize(CategoryTranslation::class);
        $firstCategory->findTranslationByLocale($locale)->willReturn($firstObject->reveal());
        $this->categoryManager->findById($ids[0])->willReturn($firstCategory->reveal());

        $secondCategory = $this->prophesize(Category::class);
        $secondObject = $this->prophesize(CategoryTranslation::class);
        $secondCategory->findTranslationByLocale($locale)->willReturn($secondObject->reveal());
        $this->categoryManager->findById($ids[1])->willReturn($secondCategory->reveal());

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

        $value = $this->categoryConverter->convert($ids, $document->reveal());

        $this->assertArrayHasKey('value', $value);
        $this->assertSame($ids, $value['value']);

        $this->assertArrayHasKey('fields', $value);
        $this->assertCount(3, $value['fields']);

        $this->assertSame('0#' . $firstFields[0]->getName(), $value['fields'][0]->getName());
        $this->assertSame($firstFields[0]->getValue(), $value['fields'][0]->getValue());

        $this->assertSame('0#' . $firstFields[1]->getName(), $value['fields'][1]->getName());
        $this->assertSame($firstFields[1]->getValue(), $value['fields'][1]->getValue());

        $this->assertSame('1#' . $secondFields[0]->getName(), $value['fields'][2]->getName());
        $this->assertSame($secondFields[0]->getValue(), $value['fields'][2]->getValue());
    }

    public function testConvertDefaultCategory(): void
    {
        $id = 1;
        $locale = 'en';

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn($locale);

        $category = $this->prophesize(Category::class);
        $object = $this->prophesize(CategoryTranslation::class);
        $this->categoryManager->findById($id)->willReturn($category->reveal());
        $category->findTranslationByLocale($locale)->willReturn(false);
        $category->getDefaultLocale()
            ->willReturn('de')
            ->shouldBeCalled();
        $category->findTranslationByLocale('de')
            ->willReturn($object->reveal())
            ->shouldBeCalled();

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

        $value = $this->categoryConverter->convert($id, $document->reveal());

        $this->assertSame([
            'value' => $id,
            'fields' => $fields,
        ], $value);
    }

    public function testConvertCategoryNotFound(): void
    {
        $ids = [1];
        $locale = 'en';

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn($locale);

        $this->categoryManager->findById(Argument::any())
            ->willThrow(new CategoryIdNotFoundException(1));

        $firstIndexMetadata = $this->prophesize(ClassMetadata::class);
        $firstDefaultIndexMetadata = $this->prophesize(IndexMetadata::class);
        $firstIndexMetadata->getIndexMetadata('_default')->willReturn($firstDefaultIndexMetadata->reveal());

        $value = $this->categoryConverter->convert($ids, $document->reveal());

        $this->assertArrayHasKey('value', $value);
        $this->assertSame($ids, $value['value']);

        $this->assertArrayHasKey('fields', $value);
        $this->assertCount(0, $value['fields']);
    }
}
