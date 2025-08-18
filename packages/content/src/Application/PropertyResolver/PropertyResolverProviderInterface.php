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

namespace Sulu\Content\Application\PropertyResolver;

use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

interface PropertyResolverProviderInterface
{
    public function getPropertyResolver(string $type): PropertyResolverInterface;
}
