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

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;

/**
 * FaxType.
 */
class FaxType implements \JsonSerializable
{
    /**
     * @var string
     * @Groups({"fullAccount", "fullContact"})
     */
    private $name;

    /**
     * @var int
     * @Groups({"fullAccount", "fullContact"})
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $faxes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->faxes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name.
     *
     * @param string $name
     *
     * @return FaxType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     *
     * @return FaxType
     */
    public function addFaxe(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes[] = $faxes;

        return $this;
    }

    /**
     * Remove faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFaxe(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes->removeElement($faxes);
    }

    /**
     * Get faxes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFaxes()
    {
        return $this->faxes;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
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

    /**
     * Add faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     *
     * @return FaxType
     */
    public function addFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes[] = $faxes;

        return $this;
    }

    /**
     * Remove faxes.
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Fax $faxes
     */
    public function removeFax(\Sulu\Bundle\ContactBundle\Entity\Fax $faxes)
    {
        $this->faxes->removeElement($faxes);
    }
}
