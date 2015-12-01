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
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Represents account item in contact data provider.
 *
 * @ExclusionPolicy("all")
 */
class AccountDataItem implements ItemInterface
{
    /**
     * @var Account
     */
    private $entity;

    public function __construct(Account $entity)
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
        return $this->entity->getName();
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
