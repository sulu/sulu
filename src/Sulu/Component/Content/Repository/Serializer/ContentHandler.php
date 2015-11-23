<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\Content\Repository\Content;

/**
 * Seializes content objects to json.
 */
class ContentHandler
{
    /**
     * Serializes content data to json array.
     *
     * @param JsonSerializationVisitor $visitor
     * @param Content $content
     * @param array $type
     * @param Context $context
     *
     * @return array
     */
    public function serializeContentToJson(
        JsonSerializationVisitor $visitor,
        Content $content,
        array $type,
        Context $context
    ) {
        return $content->jsonSerialize();
    }
}
