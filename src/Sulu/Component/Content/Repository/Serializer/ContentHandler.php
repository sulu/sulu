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
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Serializes content objects to json.
 */
class ContentHandler
{
    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        AccessControlManagerInterface $accessControlManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->accessControlManager = $accessControlManager;
        $this->tokenStorage = $tokenStorage;
    }

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
            $result['_linked'] = 'external';
        } elseif (RedirectType::INTERNAL === $content->getNodeType()) {
            $result['_linked'] = 'internal';
        }
        $result['_permissions'] = $this->accessControlManager->getUserPermissionByArray(
            $content->getLocale(),
            ContentAdmin::SECURITY_CONTEXT_PREFIX . $content->getWebspaceKey(),
            $content->getPermissions(),
            $this->tokenStorage->getToken()->getUser()
        );
        if (null !== $content->getLocalizationType()) {
            $result['_type'] = $content->getLocalizationType()->toArray();
        }

        return $result;
    }
}
