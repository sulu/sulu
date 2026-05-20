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

namespace Sulu\Content\Application\SmartResolver\Resolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;

interface SmartResolverInterface
{
    /**
     * @param array<string, mixed> $context Render context (e.g. `_sourceDimensionContent`, `_renderReferences`).
     */
    public function resolve(SmartResolvable $resolvable, ?string $locale = null, array $context = []): ContentView;

    public static function getType(): string;
}
