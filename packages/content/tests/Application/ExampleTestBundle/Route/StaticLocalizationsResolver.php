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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Route;

use Sulu\Content\Application\ContentLocalizationsResolver\ContentLocalizationsResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Registered for a resource key of its own, so a test sees which resolver the container picked.
 */
class StaticLocalizationsResolver implements ContentLocalizationsResolverInterface
{
    /**
     * @param array<string, array{url: string, locale: string, alternate: bool}> $localizations
     */
    public function __construct(
        private readonly array $localizations,
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, string $webspaceKey): array
    {
        return $this->localizations;
    }
}
