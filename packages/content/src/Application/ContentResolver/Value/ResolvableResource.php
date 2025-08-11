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
class ResolvableResource implements ResolvableInterface
{
    private \Closure $callback;

    /**
     * @param array<string, mixed>|null $metadata Optional metadata for additional context
     */
    public function __construct(
        private string|int $id,
        private string $resourceLoaderKey,
        private int $priority,
        ?\Closure $resourceCallback = null,
        private ?array $metadata = null,
    ) {
        $this->callback = $resourceCallback ?? (static fn (mixed $resource) => $resource);
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getResourceLoaderKey(): string
    {
        return $this->resourceLoaderKey;
    }

    public function executeResourceCallback(mixed $resource): mixed
    {
        return ($this->callback)($resource);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getMetadataIdentifier(): string
    {
        // TODO do we need a rekursive key sort?
        return $this->metadata ? \md5((string) \json_encode($this->metadata)) : '';
    }
}
