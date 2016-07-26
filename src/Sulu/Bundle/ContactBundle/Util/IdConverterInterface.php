<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Util;

/**
 * Interface for id converters.
 */
interface IdConverterInterface
{
    /**
     * Converts array of ids with group into a array of grouped ids.
     *
     * @param array $ids
     * @param array $default
     *
     * @return array
     */
    public function convertIdsToGroupedIds(array $ids, array $default = []);

    /**
     * Converts array of grouped ids in a array of ids with groups.
     *
     * @param array $groupedIds
     *
     * @return array
     */
    public function convertGroupedIdsToIds(array $groupedIds);
}
