<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

class CategoryMetaRepository extends EntityRepository implements CategoryMetaRepositoryInterface
{
}
