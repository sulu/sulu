<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;

return new Index('admin', [
    'id' => new Field\IdentifierField('id'),
    'resourceKey' => new Field\TextField('resourceKey', searchable: false, filterable: true),
    'resourceId' => new Field\TextField('resourceId', searchable: false),
    'locale' => new Field\TextField('locale', searchable: false, filterable: true),
    'securityContext' => new Field\TextField('securityContext', searchable: false, filterable: true),
    'title' => new Field\TextField('title'),
    'mediaId' => new Field\IntegerField('mediaId'),
    'changedAt' => new Field\DateTimeField('changedAt'),
    'createdAt' => new Field\DateTimeField('createdAt'),
    'metadata' => new Field\JsonObjectField('metadata'),
]);
