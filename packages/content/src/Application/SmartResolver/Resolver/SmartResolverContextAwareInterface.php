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

/**
 * Implement this interface for smart resolvers that need the context of the current resolution, such as the
 * resolved resource in "resource".
 */
interface SmartResolverContextAwareInterface extends SmartResolverInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function resolve(SmartResolvable $resolvable, ?string $locale = null, array $context = []): ContentView;
}
