<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata\PropertyMetadataMapper;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\EmailPropertyMetadataMapper;

class EmailPropertyMetadataMapperTest extends TestCase
{
    private EmailPropertyMetadataMapper $emailPropertyMetadataMapper;

    protected function setUp(): void
    {
        $this->emailPropertyMetadataMapper = new EmailPropertyMetadataMapper();
    }

    /**
     * @return array{type: 'null'}
     */
    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    /**
     * @return array{
     *     type: 'string',
     *     maxLength: 0,
     * }
     */
    private function getEmptyStringSchema(): array
    {
        return [
            'type' => 'string',
            'maxLength' => 0,
        ];
    }

    public function testMapPropertyMetadata(): void
    {
        $fieldMetadata = new FieldMetadata('property-name');

        $jsonSchema = $this->emailPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
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
        $fieldMetadata = new FieldMetadata('property-name');
        $fieldMetadata->setRequired(true);

        $jsonSchema = $this->emailPropertyMetadataMapper->mapPropertyMetadata($fieldMetadata)->toJsonSchema();

        $this->assertEquals([
            'type' => 'string',
            'format' => 'idn-email',
        ], $jsonSchema);
    }
}
