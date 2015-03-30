<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Prophecy\Argument;
use Sulu\Component\Security\Authentication\SecurityIdentityInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SymfonySecurityIdentityInterface;

class SymfonyAccessControlManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SymfonyAccessControlManager
     */
    private $accessControlManager;

    /**
     * @var MutableAclProviderInterface
     */
    private $aclProvider;

    /**
     * @var MaskConverterInterface
     */
    private $maskConverter;

    /**
     * @var SecurityIdentityInterface
     */
    private $securityIdentity;

    /**
     * @var MutableAclInterface
     */
    private $acl;

    public function setUp()
    {
        parent::setUp();

        $this->aclProvider = $this->prophesize(MutableAclProviderInterface::class);
        $this->maskConverter = $this->prophesize(MaskConverterInterface::class);
        $this->securityIdentity = new RoleSecurityIdentity('SULU_ROLE_ADMINISTRATOR');
        $this->acl = $this->prophesize(MutableAclInterface::class);

        $this->accessControlManager = new SymfonyAccessControlManager(
            $this->aclProvider->reveal(),
            $this->maskConverter->reveal()
        );
    }

    public static function provideObjectIdentifiers()
    {
        return array(
            array('1', 'Acme\Example', null, '1'),
            array('1', 'Acme\Example', 'de', '1'),
        );
    }

    /**
     * @dataProvider provideObjectIdentifiers
     */
    public function testGetPermissions($objectId, $objectType, $locale, $objectIdentifier)
    {
        $ace1 = $this->prophesize(EntryInterface::class);
        $ace1->getSecurityIdentity()->willReturn($this->securityIdentity);
        $ace1->getMask()->willReturn(64);

        $this->acl->getObjectAces()->willReturn(array($ace1->reveal()));

        $this->maskConverter->convertPermissionsToArray(64)->willReturn(array('view' => true));

        $this->aclProvider->findAcl(new ObjectIdentity($objectIdentifier, $objectType))
            ->willReturn($this->acl->reveal());

        $permissions = $this->accessControlManager->getPermissions($objectType, $objectId, $locale);

        $this->assertEquals(true, $permissions['SULU_ROLE_ADMINISTRATOR']['view']);
    }

    /**
     * @dataProvider provideObjectIdentifiers
     */
    public function testGetPermissionsNotAvailable($objectId, $objectType, $locale, $objectIdentifier)
    {
        $this->aclProvider->findAcl(new ObjectIdentity($objectIdentifier, $objectType))
            ->willThrow(AclNotFoundException::class);

        $permissions = $this->accessControlManager->getPermissions($objectType, $objectId, $locale);

        $this->assertEquals(array(), $permissions);
    }

    /**
     * @dataProvider provideObjectIdentifiers
     */
    public function testSetPermissionsWithExistingAcl($objectId, $objectType, $locale, $objectIdentifier)
    {
        $symfonySecurityIdentity = $this->prophesize(SymfonySecurityIdentityInterface::class);
        $symfonySecurityIdentity->equals(Argument::any())->willReturn(true);

        $ace = $this->prophesize(Entry::class);
        $ace->getSecurityIdentity()->willReturn($symfonySecurityIdentity);
        $ace->getId()->willReturn(0);

        $this->aclProvider->findAcl(new ObjectIdentity($objectIdentifier, $objectType))
            ->willReturn($this->acl->reveal())->shouldBeCalled();
        $this->aclProvider->createAcl(Argument::cetera())->shouldNotBeCalled();
        $this->aclProvider->updateAcl($this->acl->reveal())->shouldBeCalled();

        $this->acl->getObjectAces()->willReturn(array($ace->reveal()));

        $this->acl->updateObjectAce(0, Argument::any())->shouldBeCalled();

        $this->accessControlManager->setPermissions(
            $objectType,
            $objectId,
            $this->securityIdentity,
            array('view'),
            $locale
        );
    }

    /**
     * @dataProvider provideObjectIdentifiers
     */
    public function testSetPermissionsWithExistingAclWithoutAce($objectId, $objectType, $locale, $objectIdentifier)
    {
        $this->aclProvider->findAcl(new ObjectIdentity($objectIdentifier, $objectType))
            ->willReturn($this->acl->reveal())->shouldBeCalled();
        $this->aclProvider->createAcl(new ObjectIdentity($objectIdentifier, $objectType))->shouldNotBeCalled();
        $this->aclProvider->updateAcl($this->acl->reveal())->shouldBeCalled();

        $this->acl->getObjectAces()->willReturn(array());

        $this->acl->insertObjectAce(Argument::cetera())->shouldBeCalled();

        $this->accessControlManager->setPermissions(
            $objectType,
            $objectId,
            $this->securityIdentity,
            array('view'),
            $locale
        );
    }

    /**
     * @dataProvider provideObjectIdentifiers
     */
    public function testSetPermissionsWithoutExistingAcl($objectId, $objectType, $locale, $objectIdentifier)
    {
        $this->aclProvider->findAcl(new ObjectIdentity($objectIdentifier, $objectType))->willThrow(AclNotFoundException::class);
        $this->aclProvider->createAcl(new ObjectIdentity($objectIdentifier, $objectType))
            ->willReturn($this->acl->reveal())->shouldBeCalled();
        $this->aclProvider->updateAcl($this->acl->reveal())->shouldBeCalled();

        $this->acl->getObjectAces()->willReturn(array());

        $this->acl->insertObjectAce(Argument::cetera())->shouldBeCalled();

        $this->accessControlManager->setPermissions(
            $objectType,
            $objectId,
            $this->securityIdentity,
            array('view'),
            $locale
        );
    }
}
