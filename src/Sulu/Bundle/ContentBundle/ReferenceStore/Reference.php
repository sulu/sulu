<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\ReferenceStore;

use Ramsey\Uuid\Uuid;

/**
 * Represents single reference.
 */
class Reference
{
    const DELIMITER = '-';

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $id;

    /**
     * @param string $alias
     * @param string $id
     */
    public function __construct($alias, $id)
    {
        $this->alias = $alias;
        $this->id = $id;
    }

    /**
     * Returns alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Returns id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns true if the id is a valid uuid.
     *
     * @return bool
     */
    public function isUuid()
    {
        return Uuid::isValid($this->id);
    }

    public function __toString()
    {
        if ($this->isUuid()) {
            return $this->id;
        }

        return $this->alias . self::DELIMITER . $this->id;
    }
}
