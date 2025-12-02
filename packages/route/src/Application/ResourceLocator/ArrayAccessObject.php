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

namespace Sulu\Route\Application\ResourceLocator;

/**
 * A wrapper class that allows both property access (object.title) and
 * array access (object['title']) syntax in ExpressionLanguage expressions.
 *
 * @implements \ArrayAccess<string, mixed>
 */
final class ArrayAccessObject implements \ArrayAccess
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data,
    ) {
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('ArrayAccessObject is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('ArrayAccessObject is read-only.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getArrayCopy(): array
    {
        return $this->data;
    }
}
