<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\ReferenceStore;

/**
 * Indicates missing reference-store.
 */
class ReferenceStoreNotExistsException extends \Exception
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @var string[]
     */
    private $availableStores;

    /**
     * @param string $alias
     * @param string[] $availableStores
     */
    public function __construct($alias, $availableStores)
    {
        parent::__construct(
            sprintf(
                'ReferenceStore with alias "%s" not exists. Available stores: "%s"',
                $alias,
                implode('", "', $availableStores)
            )
        );

        $this->alias = $alias;
        $this->availableStores = $availableStores;
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
     * Returns availableStores.
     *
     * @return string[]
     */
    public function getAvailableStores()
    {
        return $this->availableStores;
    }
}
