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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Value;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;

class SmartResolvableTest extends TestCase
{
    public function testWithContextReturnsACloneCarryingTheContext(): void
    {
        $smartResolvable = new SmartResolvable(['value' => []], 'smart_example', 1, null, ['key' => 'value']);
        $context = ['resource' => ['resourceKey' => 'pages', 'id' => '123']];

        $withContext = $smartResolvable->withContext($context);

        self::assertNotSame($smartResolvable, $withContext);
        self::assertSame($context, $withContext->getContext());
        self::assertSame([], $smartResolvable->getContext());
    }

    public function testWithContextKeepsTheMetadataIdentifier(): void
    {
        $smartResolvable = new SmartResolvable(['value' => []], 'smart_example', 1, null, ['key' => 'value']);

        $withContext = $smartResolvable->withContext(['resource' => ['id' => '123']]);

        self::assertSame($smartResolvable->getMetadataIdentifier(), $withContext->getMetadataIdentifier());
        self::assertSame(['key' => 'value'], $withContext->getMetadata());
    }
}
