<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\Doctrine\Odm\MongoDb;

use Doctrine\ODM\MongoDB\DocumentRepository as BaseDocumentRepository;
use Sulu\Component\Persistence\Repository\RepositoryInterface;

class DocumentRepository extends BaseDocumentRepository implements RepositoryInterface
{

}
