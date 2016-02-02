<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Psr\Log\LoggerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Acl\Voter\AclVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter deciding on object based security.
 *
 * @deprecated will be removed with 1.2
 */
class SymfonyAccessControlVoter extends AclVoter
{
    /**
     * @var AclProviderInterface
     */
    private $aclProvider;

    public function __construct(
        AclProviderInterface $aclProvider,
        ObjectIdentityRetrievalStrategyInterface $oidRetrievalStrategy,
        SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy,
        PermissionMapInterface $permissionMap,
        LoggerInterface $logger = null,
        $allowIfObjectIdentityUnavailable = true
    ) {
        parent::__construct(
            $aclProvider,
            $oidRetrievalStrategy,
            $sidRetrievalStrategy,
            $permissionMap,
            $logger,
            $allowIfObjectIdentityUnavailable
        );

        $this->aclProvider = $aclProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object instanceof SecurityCondition) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if ($object->getObjectType() === null || $object->getObjectId() === null) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        try {
            $objectIdentity = new ObjectIdentity($object->getObjectId(), $object->getObjectType());
            $this->aclProvider->findAcl($objectIdentity); // only called to check if acl exists

            return parent::vote($token, $objectIdentity, $attributes);
        } catch (AclNotFoundException $exc) {
            return VoterInterface::ACCESS_ABSTAIN;
        }
    }
}
