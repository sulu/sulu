<?php

declare(strict_types=1);

namespace Sulu\Content\Application\ContentResolver\Value;

/**
 * @internal This class is intended for internal use only within the package/library. Modifying or depending on this class may result in unexpected behavior and is not supported.
 */
class SmartResolvable implements ResolvableInterface
{
    /**
     * @param mixed[] $data
     */
    public function __construct(
        private array $data,
        private string $resourceLoaderKey,
        private int $priority,
    ) {
    }

    public function getId(): string|int
    {
        return \spl_object_hash($this);
    }

    public function getResourceLoaderKey(): string
    {
        return $this->resourceLoaderKey;
    }

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
