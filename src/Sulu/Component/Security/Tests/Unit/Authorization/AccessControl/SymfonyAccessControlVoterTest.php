<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization\AccessControl;

use Prophecy\Argument;
use Sulu\Component\Security\Authorization\AccessControl\SymfonyAccessControlVoter;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SymfonyAccessControlVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SymfonyAccessControlVoter
     */
    private $accessControlVoter;

    /**
     * @var MutableAclProviderInterface
     */
    private $aclProvider;

    /**
     * @var ObjectIdentityRetrievalStrategyInterface
     */
    private $objectIdentityRetrievalStrategy;

    /**
     * @var SecurityIdentityRetrievalStrategyInterface
     */
    private $securityIdentityRetrievalStrategy;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var PermissionMapInterface
     */
    private $permissionMap;

    public function setUp()
    {
        parent::setUp();

        $this->aclProvider = $this->prophesize(MutableAclProviderInterface::class);
        $this->objectIdentityRetrievalStrategy = $this->prophesize(ObjectIdentityRetrievalStrategyInterface::class);
        $this->securityIdentityRetrievalStrategy = $this->prophesize(SecurityIdentityRetrievalStrategyInterface::class);
        $this->permissionMap = $this->prophesize(PermissionMapInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);

        $this->accessControlVoter = new SymfonyAccessControlVoter(
            $this->aclProvider->reveal(),
            $this->objectIdentityRetrievalStrategy->reveal(),
            $this->securityIdentityRetrievalStrategy->reveal(),
            $this->permissionMap->reveal()
        );
    }

    public function testVoteWithoutAcl()
    {
        $this->aclProvider->findAcl(Argument::cetera())->willThrow(AclNotFoundException::class);

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->accessControlVoter->vote(
                $this->token->reveal(),
                new SecurityCondition('acme_example', null, '1', 'Acme\Example'),
                []
            )
        );
    }
}
