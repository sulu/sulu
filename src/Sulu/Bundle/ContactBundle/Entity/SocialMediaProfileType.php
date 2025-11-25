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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

#[ExclusionPolicy('All')]
class SocialMediaProfileType implements \JsonSerializable
{
    private int $id;

    private string $name;

    /**
     * @var Collection<int, SocialMediaProfile>
     */
    private Collection $socialMediaProfiles;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->socialMediaProfiles = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        // Limit to maximal sql column length.
        $this->name = \substr($name, 0, 100);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('name')]
    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    public function getName(): string
    {
        return $this->name;
    }

    public function addSocialMediaProfile(SocialMediaProfile $socialMediaProfile): static
    {
        $this->socialMediaProfiles[] = $socialMediaProfile;

        return $this;
    }

    public function removeSocialMediaProfile(SocialMediaProfile $socialMediaProfile): static
    {
        $this->socialMediaProfiles->removeElement($socialMediaProfile);

        return $this;
    }

    /**
     * @return Collection<int, SocialMediaProfile>
     */
    public function getSocialMediaProfiles(): Collection
    {
        return $this->socialMediaProfiles;
    }

    /**
     * @return array{id: int, name: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }
}
