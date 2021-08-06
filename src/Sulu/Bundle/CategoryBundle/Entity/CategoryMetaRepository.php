<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

/**
 * @extends EntityRepository<CategoryMetaInterface>
 */
class CategoryMetaRepository extends EntityRepository implements CategoryMetaRepositoryInterface
{
}
