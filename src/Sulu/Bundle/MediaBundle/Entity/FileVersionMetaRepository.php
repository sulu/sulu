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

use Doctrine\ORM\EntityRepository;

class FileVersionMetaRepository extends EntityRepository implements FileVersionMetaRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findLatest()
    {
        $queryBuilder = $this->createQueryBuilder('fileVersionMeta')
            ->leftJoin('fileVersionMeta.fileVersion', 'fileVersion')
            ->leftJoin('fileVersion.file', 'file')
            ->where('file.version = fileVersion.version');

        return $queryBuilder->getQuery()->getResult();
    }
}
