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
    public function testWithContextAddsTheContextToTheMetadata(): void
    {
        $smartResolvable = new SmartResolvable(['value' => []], 'smart_content', 2048, null, ['name' => 'blocks']);

        $withContext = $smartResolvable->withContext(['selfReference' => ['resourceKey' => 'pages', 'id' => '123']]);

        self::assertSame(
            ['name' => 'blocks', 'context' => ['selfReference' => ['resourceKey' => 'pages', 'id' => '123']]],
            $withContext->getMetadata(),
        );
        self::assertSame(['value' => []], $withContext->getData());
        self::assertSame('smart_content', $withContext->getResourceLoaderKey());
        self::assertSame(2048, $withContext->getPriority());
    }

    public function testWithContextKeepsTheOriginalUnchanged(): void
    {
        $smartResolvable = new SmartResolvable(['value' => []], 'smart_content', 2048, null, ['name' => 'blocks']);
        $metadataIdentifier = $smartResolvable->getMetadataIdentifier();

        $smartResolvable->withContext(['selfReference' => ['resourceKey' => 'pages', 'id' => '123']]);

        self::assertSame(['name' => 'blocks'], $smartResolvable->getMetadata());
        self::assertSame($metadataIdentifier, $smartResolvable->getMetadataIdentifier());
    }

    public function testWithContextKeepsTheResourceCallback(): void
    {
        $smartResolvable = new SmartResolvable(['value' => []], 'smart_content', 2048, static fn (string $resource): string => $resource . '!');

        self::assertSame('loaded!', $smartResolvable->withContext([])->executeResourceCallback('loaded'));
    }
}
