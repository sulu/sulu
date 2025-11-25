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

class Position implements \JsonSerializable
{
    public const RESOURCE_KEY = 'contact_positions';

    #[Groups(['fullContact', 'partialContact'])]
    private string $position;

    #[Groups(['fullContact', 'partialContact'])]
    private ?int $id = null;

    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return array{id: int|null, position: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'position' => $this->getPosition(),
        ];
    }
}
