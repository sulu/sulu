<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Search;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Field;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Search\TagsConverter;
use Sulu\Bundle\TagBundle\Tag\TagManager;

class TagsConverterTest extends TestCase
{
    use ProphecyTrait;

    public function testConvert(): void
    {
        $tagManager = $this->prophesize(TagManager::class);
        $tagsConverter = new TagsConverter($tagManager->reveal());

        $tagManager->resolveTagNames(['Tag1', 'Tag2', 'Tag3'])->willReturn([1, 2, 3]);

        $this->assertEquals([1, 2, 3], $tagsConverter->convert(['Tag1', 'Tag2', 'Tag3']));
    }

    public function testConvertNull(): void
    {
        $tagManager = $this->prophesize(TagManager::class);
        $tagsConverter = new TagsConverter($tagManager->reveal());

        $this->assertNull(
            $tagsConverter->convert(null)
        );
    }

    public function testConvertWithDocumentAndNull(): void
    {
        $tagManager = $this->prophesize(TagManager::class);
        $tagsConverter = new TagsConverter($tagManager->reveal());

        $this->assertNull(
            $tagsConverter->convert(null, $this->prophesize(Document::class)->reveal())
        );
    }

    public function testConvertWithDocumentAndStringValue(): void
    {
        $tagManager = $this->prophesize(TagManager::class);
        $tagsConverter = new TagsConverter($tagManager->reveal());

        $originalValue = 'Tag1';

        $tag = new Tag();
        $tag->setId(1);
        $tag->setName($originalValue);
        $tagManager->findByName($originalValue)->willReturn($tag);

        $value = $tagsConverter->convert($originalValue, $this->prophesize(Document::class)->reveal());

        $this->assertArrayHasKey('value', $value);
        $this->assertSame($tag->getId(), $value['value']);

        $this->assertArrayHasKey('fields', $value);
        $this->assertCount(2, $value['fields']);

        /** @var Field $idField */
        $idField = $value['fields'][0];
        $this->assertSame('id', $idField->getName());
        $this->assertSame($tag->getId(), $idField->getValue());

        /** @var Field $nameField */
        $nameField = $value['fields'][1];
        $this->assertSame('name', $nameField->getName());
        $this->assertSame($tag->getName(), $nameField->getValue());
    }

    public function testConvertWithDocumentAndArrayValue(): void
    {
        $tagManager = $this->prophesize(TagManager::class);
        $tagsConverter = new TagsConverter($tagManager->reveal());

        $originalValue = ['Tag1', 'Tag2'];
        $ids = [1, 2];
        $tagManager->resolveTagNames($originalValue)->willReturn($ids);

        $value = $tagsConverter->convert($originalValue, $this->prophesize(Document::class)->reveal());

        $this->assertArrayHasKey('value', $value);
        $this->assertSame($ids, $value['value']);

        $this->assertArrayHasKey('fields', $value);
        $this->assertCount(4, $value['fields']);

        /** @var Field $firstIdField */
        $firstIdField = $value['fields'][0];
        $this->assertSame('0#id', $firstIdField->getName());
        $this->assertSame($ids[0], $firstIdField->getValue());

        /** @var Field $firstNameField */
        $firstNameField = $value['fields'][1];
        $this->assertSame('0#name', $firstNameField->getName());
        $this->assertSame($originalValue[0], $firstNameField->getValue());

        /** @var Field $secondIdField */
        $secondIdField = $value['fields'][2];
        $this->assertSame('1#id', $secondIdField->getName());
        $this->assertSame($ids[1], $secondIdField->getValue());

        /** @var Field $secondNameField */
        $secondNameField = $value['fields'][3];
        $this->assertSame('1#name', $secondNameField->getName());
        $this->assertSame($originalValue[1], $secondNameField->getValue());
    }
}
