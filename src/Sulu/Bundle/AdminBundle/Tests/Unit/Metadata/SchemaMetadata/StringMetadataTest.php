<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

class StringMetadataTest extends TestCase
{
    public function testToJsonSchema(): void
    {
        $stringMetadata = new StringMetadata(1, 2, 'foo', 'bar');

        $this->assertEquals([
            'type' => 'string',
            'minLength' => 1,
            'maxLength' => 2,
            'pattern' => 'foo',
            'format' => 'bar',
        ], $stringMetadata->toJsonSchema());
    }

    public function testToJsonSchemaWithoutValues(): void
    {
        $stringMetadata = new StringMetadata();

        $this->assertEquals(['type' => 'string'], $stringMetadata->toJsonSchema());
    }
}
