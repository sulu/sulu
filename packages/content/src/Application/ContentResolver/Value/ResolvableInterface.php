<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentResolver\Value;

interface ResolvableInterface
{
    public function getId(): string|int;

    public function getResourceLoaderKey(): string;

    public function getPriority(): int;

    public function executeResourceCallback(mixed $resource): mixed;
}
