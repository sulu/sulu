<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use Sulu\Component\SmartContent\Exception\DataProviderAliasAlreadyExistsException;
use Sulu\Component\SmartContent\Exception\DataProviderNotExistsException;

/**
 * Interface for DataProviderPool.
 */
interface DataProviderPoolInterface
{
    /**
     * Add new DataProvider with given alias.
     *
     * @param string $alias identify the DataProvider
     * @param DataProviderInterface $provider
     *
     * @throws DataProviderAliasAlreadyExistsException
     */
    public function add($alias, DataProviderInterface $provider);

    /**
     * Returns TRUE if DataProvider exists otherwise FALSE.
     *
     * @param string $alias identify the DataProvider
     *
     * @return bool
     */
    public function exists($alias);

    /**
     * Returns DataProvider with given alias.
     *
     * @param string $alias identify the DataProvider
     *
     * @return DataProviderInterface
     *
     * @throws DataProviderNotExistsException
     */
    public function get($alias);

    /**
     * Returns all registered DataProvider.
     *
     * @return DataProviderInterface[]
     */
    public function getAll();
}
