<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\ReferenceStore;

use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Reference store implementation for HTTP cache bundle.
 * Stores references with resource keys for cache tag generation.
 */
class ReferenceStore implements ReferenceStoreInterface, ResetInterface
{
    /**
     * @var array<string, array<string>>
     */
    private array $references = [];

    public function add(string $id, string $resourceKey): void
    {
        if (!isset($this->references[$resourceKey])) {
            $this->references[$resourceKey] = [];
        }

        if (\in_array($id, $this->references[$resourceKey], true)) {
            return;
        }

        $this->references[$resourceKey][] = $id;
    }

    /**
     * @return array<string>
     */
    public function getAll(): array
    {
        $tags = [];

        foreach ($this->references as $resourceKey => $ids) {
            foreach ($ids as $id) {
                $tag = $id;
                if (!Uuid::isValid($id)) {
                    $tag = $resourceKey . '-' . $id;
                }
                // use the tag as key to prevent duplicates
                $tags[$tag] = $tag;
            }
        }

        return $tags;
    }

    public function reset(): void
    {
        $this->references = [];
    }
}
