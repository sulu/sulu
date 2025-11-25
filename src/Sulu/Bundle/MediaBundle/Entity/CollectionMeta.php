<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

class CollectionMeta
{
    private int $id;

    private string $title;

    private ?string $description = null;

    private string $locale;

    private CollectionInterface $collection;

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setCollection(CollectionInterface $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection(): CollectionInterface
    {
        return $this->collection;
    }
}
