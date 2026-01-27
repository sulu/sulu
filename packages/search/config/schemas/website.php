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

return new Index('website', [
    'id' => new Field\IdentifierField('id'),
    'resourceKey' => new Field\TextField('resourceKey', searchable: false, filterable: true),
    'resourceId' => new Field\TextField('resourceId', searchable: false),
    'locale' => new Field\TextField('locale', searchable: false, filterable: true),
    'webspaces' => new Field\TextField('webspaces', multiple: true, searchable: false, filterable: true),
    'title' => new Field\TextField('title'),
    'url' => new Field\TextField('url', searchable: false, filterable: true),
    'content' => new Field\TextField('content', multiple: true),
    'mediaId' => new Field\IntegerField('mediaId'),
    'authoredAt' => new Field\DateTimeField('authoredAt'),
    'properties' => new Field\JsonObjectField('properties'),
]);
