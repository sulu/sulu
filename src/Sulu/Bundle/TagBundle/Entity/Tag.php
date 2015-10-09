<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Entity;

use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Tag.
 */
class Tag extends ApiEntity implements AuditableInterface
{
    /**
     * @var string
     * @Expose
     * @Groups({"partialTag"})
     */
    private $name;

    /**
     * @var int
     * @Groups({"partialTag"})
     */
    private $id;

    /**
     * @var \DateTime
     * @Groups({"partialTag"})
     */
    private $created;

    /**
     * @var \DateTime
     * @Groups({"partialTag"})
     */
    private $changed;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $creator;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Tag
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
     * Set id.
     *
     * @param int $id
     *
     * @return Tag
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set changer.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $changer
     *
     * @return Tag
     */
    public function setChanger(\Sulu\Component\Security\Authentication\UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $creator
     *
     * @return Tag
     */
    public function setCreator(\Sulu\Component\Security\Authentication\UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getName();
    }
}
