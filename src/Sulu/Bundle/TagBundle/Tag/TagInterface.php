<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Interface for tag.
 */
interface TagInterface extends AuditableInterface
{
    /**
     * Set name.
     *
     * @param string $name
     *
     * @return TagInterface
     */
    public function setName($name);

    /**
     * Get name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return TagInterface
     */
    public function setId($id);
}
