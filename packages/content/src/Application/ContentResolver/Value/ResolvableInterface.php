<?php

namespace Sulu\Content\Application\ContentResolver\Value;

interface ResolvableInterface
{
    public function getId(): string|int;

    public function getResourceLoaderKey(): string;

    public function getPriority(): int;

    public function executeResourceCallback(mixed $resource): mixed;
}
