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

/**
 * Indicates invalid id for reference-store.
 */
class ReferenceStoreInvalidIdException extends \Exception
{
    /**
     * @var string
     */
    private $id;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        parent::__construct(
            sprintf('Invalid reference-store id "%s". Delimiter "%s" not found.', $id, ChainReferenceStore::DELIMITER)
        );

        $this->id = $id;
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
}
