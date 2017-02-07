<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;

use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Checks the Sulu security.
 */
class SecurityContextVoter implements VoterInterface
{
    /**
     * The permissions available, defined by config.
     *
     * @var array
     */
    private $permissions;

    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    public function __construct(AccessControlManagerInterface $accessControlManager, $permissions)
    {
        $this->accessControlManager = $accessControlManager;
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array_keys($this->permissions));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === SecurityCondition::class || is_subclass_of($class, SecurityCondition::class);
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!is_object($object) ||
            !$this->supportsClass(get_class($object))
        ) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $userPermissions = $this->accessControlManager->getUserPermissions($object, $user);

        // only if all attributes are granted the access is granted
        foreach ($attributes as $attribute) {
            if (isset($userPermissions[$attribute]) && !$userPermissions[$attribute]) {
                return VoterInterface::ACCESS_DENIED;
            }
        }

        return VoterInterface::ACCESS_GRANTED;
    }
}
