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

namespace Sulu\Content\Application\ContentResolver\Value;

/**
 * @internal This class is intended for internal use only within the package/library. Modifying or depending on this class may result in unexpected behavior and is not supported.
 */
class SmartResolvable implements ResolvableInterface
{
    private \Closure $callback;

    /**
     * @param mixed[] $data
     */
    public function __construct(
        private array $data,
        private string $resourceLoaderKey,
        private int $priority,
        ?\Closure $resourceCallback = null,
    ) {
        $this->callback = $resourceCallback ?? (static fn (mixed $resource) => $resource);
    }

    public function getId(): string|int
    {
        return \spl_object_hash($this);
    }

    public function getResourceLoaderKey(): string
    {
        return $this->resourceLoaderKey;
    }

    public function executeResourceCallback(mixed $resource): mixed
    {
        return ($this->callback)($resource);
    }

    /**
     * SmartResolvable should be resolved before other resolvables, so it is recommended to have a high priority.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
