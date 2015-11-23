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
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
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
        $result = $content->jsonSerialize();
        $result['publishedState'] = (WorkflowStage::PUBLISHED === $content->getWorkflowStage());
        if (RedirectType::EXTERNAL === $content->getNodeType()) {
            $result['linked'] = 'external';
        } elseif (RedirectType::INTERNAL === $content->getNodeType()) {
            $result['linked'] = 'internal';
        }
        $result['_permissions'] = $content->getPermissions();
        if (null !== $content->getLocalizationType()) {
            $result['type'] = $content->getLocalizationType()->toArray();
        }

        return $result;
    }
}
