<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Represents contact item in contact data provider.
 *
 * @ExclusionPolicy("all")
 */
class ContactDataItem implements ItemInterface
{
    /**
     * @var Contact
     */
    private $entity;

    public function __construct(Contact $entity)
    {
        $this->entity = $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getTitle()
    {
        return $this->entity->getFullName();
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getImage()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->entity;
    }
}
