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
class ContentView
{
    /**
     * @param mixed[] $view
     * @param array<Reference> $references
     */
    private function __construct(
        private mixed $content,
        private array $view,
        private array $references = [],
    ) {
    }

    /**
     * @param mixed[] $view
     */
    public static function create(mixed $content, array $view): self
    {
        return new self($content, $view);
    }

    /**
     * @param mixed[] $view
     * @param array<Reference> $references
     */
    public static function createWithReferences(mixed $content, array $view, array $references): self
    {
        return new self($content, $view, $references);
    }

    /**
     * @param mixed[] $data
     * @param mixed[] $view
     */
    public static function createSmartResolvable(
        array $data,
        string $resourceLoaderKey,
        array $view = [],
        int $priority = 2048, // Default priority for SmartResolvable should be high
    ): self {
        return new self(
            new SmartResolvable(
                data: $data,
                resourceLoaderKey: $resourceLoaderKey,
                priority: $priority,
            ),
            $view,
        );
    }

    /**
     * @param mixed[] $view
     * @param array<string, mixed>|null $metadata
     */
    public static function createResolvable(
        string|int $id,
        string $resourceLoaderKey,
        array $view,
        int $priority = 0,
        ?\Closure $closure = null,
        ?array $metadata = null,
    ): self {
        return new self(
            new ResolvableResource(
                id: $id,
                resourceLoaderKey: $resourceLoaderKey,
                priority: $priority,
                resourceCallback: $closure,
                metadata: $metadata,
            ),
            $view,
        );
    }

    /**
     * @param mixed[] $view
     * @param array<string, mixed>|null $metadata
     */
    public static function createResolvableWithReferences(
        string|int $id,
        string $resourceLoaderKey,
        string $resourceKey,
        array $view,
        int $priority = 0,
        ?\Closure $closure = null,
        ?array $metadata = null,
    ): self {
        return new self(
            new ResolvableResource(
                id: $id,
                resourceLoaderKey: $resourceLoaderKey,
                priority: $priority,
                resourceCallback: $closure,
                metadata: $metadata,
            ),
            $view,
            [new Reference($id, $resourceKey)]
        );
    }

    /**
     * @param array<string|int> $ids
     * @param mixed[] $view
     * @param array<string, mixed>|null $metadata
     */
    public static function createResolvables(
        array $ids,
        string $resourceLoaderKey,
        array $view,
        int $priority = 0,
        ?array $metadata = null,
    ): self {
        $resolvableResources = [];

        foreach ($ids as $id) {
            $resolvableResources[] = new ResolvableResource(
                id: $id,
                resourceLoaderKey: $resourceLoaderKey,
                priority: $priority,
                metadata: $metadata
            );
        }

        return new self($resolvableResources, $view);
    }

    /**
     * @param array<string|int> $ids
     * @param mixed[] $view
     * @param array<string, mixed>|null $metadata
     */
    public static function createResolvablesWithReferences(
        array $ids,
        string $resourceLoaderKey,
        string $resourceKey,
        array $view,
        int $priority = 0,
        ?array $metadata = null,
    ): self {
        $resolvableResources = [];
        $references = [];
        foreach ($ids as $id) {
            $resolvableResources[] = new ResolvableResource(
                id: $id,
                resourceLoaderKey: $resourceLoaderKey,
                priority: $priority,
                metadata: $metadata
            );
            $references[] = new Reference($id, $resourceKey);
        }

        return new self($resolvableResources, $view, $references);
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * @return mixed[]
     */
    public function getView(): array
    {
        return $this->view;
    }

    public function setContent(mixed $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param mixed[] $view
     */
    public function setView(array $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return array<Reference>
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * @param array<Reference> $references
     */
    public function setReferences(array $references): self
    {
        $this->references = $references;

        return $this;
    }

    /**
     * @internal This method can be removed at any time no backwards compatibility promise is given for this.
     *
     * Recursively collect all references from this ContentView and nested ContentViews.
     * Each reference will have its path set to indicate where it was found.
     *
     * @return iterable<Reference>
     */
    public function getAllReferencesRecursively(string $basePath = ''): iterable
    {
        foreach ($this->references as $reference) {
            yield new Reference(
                $reference->getResourceId(),
                $reference->getResourceKey(),
                $basePath
            );
        }

        $content = $this->getContent();
        if (\is_iterable($content)) {
            foreach ($content as $key => $value) {
                $keyStr = \is_string($key) || \is_numeric($key) ? (string) $key : '';
                $newPath = \ltrim($basePath . '.' . $keyStr, '.');

                if ($value instanceof ContentView) {
                    $nestedReferences = $value->getAllReferencesRecursively($newPath);
                    foreach ($nestedReferences as $reference) {
                        yield $reference;
                    }
                } elseif (\is_iterable($value)) {
                    // Handle arrays that might contain ContentViews
                    foreach ($value as $subKey => $subValue) {
                        if ($subValue instanceof ContentView) {
                            $subKeyStr = \is_string($subKey) || \is_numeric($subKey) ? (string) $subKey : '';
                            $subPath = \ltrim($newPath . '.' . $subKeyStr, '.');
                            $nestedReferences = $subValue->getAllReferencesRecursively($subPath);
                            foreach ($nestedReferences as $reference) {
                                yield $reference;
                            }
                        }
                    }
                }
            }
        }
    }
}
