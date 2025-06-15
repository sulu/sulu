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

namespace Sulu\Content\Application\SmartResolver;

use Sulu\Content\Application\SmartResolver\Resolver\SmartResolverInterface;

interface SmartResolverProviderInterface
{
    public function getSmartResolver(string $type): SmartResolverInterface;

    public function hasSmartResolver(string $type): bool;
}
