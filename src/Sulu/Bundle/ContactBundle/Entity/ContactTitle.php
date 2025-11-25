<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\Groups;

class ContactTitle implements \JsonSerializable
{
    public const RESOURCE_KEY = 'contact_titles';

    #[Groups(['fullContact', 'partialContact'])]
    private string $title;

    #[Groups(['fullContact', 'partialContact'])]
    private int $id;

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array{id: int, title: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
        ];
    }

    public function __toString(): string
    {
        return (string) $this->getTitle();
    }
}
