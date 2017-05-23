<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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

/**
 * Type of social media profile (i.e. Facebook,...).
 *
 * @ExclusionPolicy("All")
 */
class SocialMediaProfileType implements \JsonSerializable
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Collection
     */
    private $socialMediaProfiles;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->socialMediaProfiles = new ArrayCollection();
    }

    /**
     * To force id = 1 in load fixtures.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @VirtualProperty()
     * @SerializedName("id")
     * @Groups({"fullAccount", "fullContact"})
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return SocialMediaProfileType
     */
    public function setName($name)
    {
        // Limit to maximal sql column length.
        $this->name = substr($name, 0, 100);

        return $this;
    }

    /**
     * @VirtualProperty()
     * @SerializedName("name")
     * @Groups({"fullAccount", "fullContact"})
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param SocialMediaProfile $socialMediaProfile
     *
     * @return SocialMediaProfileType
     */
    public function addSocialMediaProfile(SocialMediaProfile $socialMediaProfile)
    {
        $this->socialMediaProfiles[] = $socialMediaProfile;

        return $this;
    }

    /**
     * @param SocialMediaProfile $socialMediaProfile
     */
    public function removeSocialMediaProfile(SocialMediaProfile $socialMediaProfile)
    {
        $this->socialMediaProfiles->removeElement($socialMediaProfile);
    }

    /**
     * @return Collection
     */
    public function getSocialMediaProfiles()
    {
        return $this->socialMediaProfiles;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }
}
