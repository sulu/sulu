<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentNormalizer\Normalizer;

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Adds permission information to entities implementing SecuredEntityInterface.
 * This normalizer mimics the behavior of SecuredEntitySubscriber for Symfony Serializer.
 */
class SecuredEntityNormalizer implements NormalizerInterface
{
    public function __construct(
        private AccessControlManagerInterface $accessControlManager,
        private ?TokenStorageInterface $tokenStorage = null
    ) {
    }

    public function enhance(object $object, array $normalizedData): array
    {
        $securedEntity = null;
        if ($object instanceof SecuredEntityInterface) {
            $securedEntity = $object;
        } elseif ($object instanceof DimensionContentInterface) {
            $resource = $object->getResource();
            if ($resource instanceof SecuredEntityInterface) {
                $securedEntity = $resource;
            }
        }

        if (!$securedEntity) {
            return $normalizedData;
        }

        $allPermissions = $this->accessControlManager->getPermissions(
            \get_class($securedEntity),
            (string) $securedEntity->getId()
        );

        $user = $this->tokenStorage?->getToken()?->getUser();
        $suluUser = $user instanceof UserInterface ? $user : null;

        $permissions = $this->accessControlManager->getUserPermissionByArray(
            null,
            $securedEntity->getSecurityContext(),
            $allPermissions,
            $suluUser
        );

        $normalizedData['_permissions'] = $permissions;
        $normalizedData['_hasPermissions'] = !empty($allPermissions);

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        return [];
    }
}
