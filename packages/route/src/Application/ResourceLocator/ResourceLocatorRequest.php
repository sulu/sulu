<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\ResourceLocator;

final readonly class ResourceLocatorRequest
{
    public string $parentResourceKey;

    /**
     * @param array<string, string> $parts
     */
    public function __construct(
        public array $parts,
        public string $locale,
        public ?string $site,
        public string $resourceKey,
        public ?string $resourceId,
        public ?string $parentResourceId,
        ?string $parentResourceKey,
        public ?string $routeSchema,
    ) {
        $this->parentResourceKey = $parentResourceKey ?? $this->resourceKey;
    }
}
