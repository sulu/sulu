<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Content\Types;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PageBundle\Content\Types\Email;
use Sulu\Component\Content\Metadata\PropertyMetadata;

class EmailTest extends TestCase
{
    /**
     * @var Email
     */
    private $contentType;

    protected function setUp(): void
    {
        $this->contentType = new Email();
    }

    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    private function getEmptyStringSchema(): array
    {
        return [
            'type' => 'string',
            'maxLength' => 0,
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');

        $jsonSchema = $this->contentType->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'anyOf' => [
                $this->getNullSchema(),
                $this->getEmptyStringSchema(),
                [
                    'type' => 'string',
                    'format' => 'idn-email',
                ],
            ],
        ], $jsonSchema);
    }

    public function testMapPropertyMetadataRequired(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata->setName('property-name');
        $propertyMetadata->setRequired(true);

        $jsonSchema = $this->contentType->mapPropertyMetadata($propertyMetadata)->toJsonSchema();

        $this->assertEquals([
            'name' => 'property-name',
            'type' => 'string',
            'format' => 'idn-email',
        ], $jsonSchema);
    }
}
