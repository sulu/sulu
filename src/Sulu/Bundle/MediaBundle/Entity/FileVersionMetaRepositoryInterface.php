<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

interface FileVersionMetaRepositoryInterface
{
    /**
     * Only finds the FileVersionMeta for the latest files.
     *
     * @return FileVersionMeta[]
     */
    public function findLatestWithoutSecurity();

    /**
     * Find all FileVersionMeta for the given collection.
     *
     * @param int $collectionId
     *
     * @return FileVersionMeta[]
     */
    public function findByCollectionId($collectionId);
}
