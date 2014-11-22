<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\Repository;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\PersistenceBundle\Doctrine\Odm\MongoDb\DocumentRepository;
use Sulu\Bundle\PersistenceBundle\Doctrine\Orm\EntityRepository;
use Sulu\Bundle\PersistenceBundle\Repository\Exception\UnknownRepositoryTypeException;
use Sulu\Component\Persistence\Repository\RepositoryInterface;

class RepositoryFactory
{

    const DOCTRINE_ORM = 'doctrine/orm';

    const DOCTRINE_MONGODB_ODM = 'doctrine/mongodb-odm';

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $type
     * @param $model
     * @return RepositoryInterface
     * @throws UnknownRepositoryTypeException
     */
    public function get($type, $model)
    {
        $instance = null;

        switch ($type) {

            case self::DOCTRINE_ORM:
                $instance = $this->entityManager->getRepository($model);
                /*$instance = new EntityRepository(
                    $this->entityManager,
                    $this->entityManager->getClassMetadata($model)
                );*/
                break;

            case self::DOCTRINE_MONGODB_ODM:
                $instance = new DocumentRepository();
                break;

            default:
                throw new UnknownRepositoryTypeException();
        }

        return $instance;
    }
}
