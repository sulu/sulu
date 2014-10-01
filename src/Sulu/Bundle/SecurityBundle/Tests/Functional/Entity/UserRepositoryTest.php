<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use DateTime;
use Doctrine\ORM\Tools\SchemaTool;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;

class UserRepositoryTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var SchemaTool
     */
    protected static $tool;

    public function setUp()
    {
        $this->setUpSchema();

        // email
        $emailType = new EmailType();
        $emailType->setName('Private');
        self::$em->persist($emailType);

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        self::$em->persist($email);

        $email2 = new Email();
        $email2->setEmail('maria.musterfrau@muster.at');
        $email2->setEmailType($emailType);
        self::$em->persist($email2);

        // Contact

        $contact1 = new Contact();
        $contact1->setFirstName("Max");
        $contact1->setLastName("Muster");
        $contact1->setCreated(new DateTime());
        $contact1->setChanged(new DateTime());
        $contact1->addEmail($email);
        self::$em->persist($contact1);

        $contact2 = new Contact();
        $contact2->setFirstName('Maria');
        $contact2->setLastName('Musterfrau');
        $contact2->setCreated(new DateTime());
        $contact2->setChanged(new DateTime());
        $contact2->addEmail($email2);
        self::$em->persist($contact2);

        self::$em->flush();

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setSystem('Sulu');
        $role1->setChanged(new DateTime());
        $role1->setCreated(new DateTime());
        self::$em->persist($role1);

        $role2 = new Role();
        $role2->setName('Role2');
        $role2->setSystem('Test');
        $role2->setChanged(new DateTime());
        $role2->setCreated(new DateTime());
        self::$em->persist($role2);

        // User 1
        $user = new User();
        $user->setUsername('admin');
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact1);
        self::$em->persist($user);

        // User 2
        $user2 = new User();
        $user2->setUsername('test');
        $user2->setPassword('securepassword');
        $user2->setSalt('salt');
        $user2->setLocale('de');
        $user2->setContact($contact2);
        self::$em->persist($user2);

        self::$em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale(json_encode(array('de', 'en')));
        self::$em->persist($userRole1);

        $userRole2 = new UserRole();
        $userRole2->setRole($role2);
        $userRole2->setUser($user2);
        $userRole2->setLocale(json_encode(array('de', 'en')));
        self::$em->persist($userRole2);

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($role1);
        $permission1->setContext("Context 1");
        self::$em->persist($permission1);

        $permission2 = new Permission();
        $permission2->setPermissions(122);
        $permission2->setRole($role2);
        $permission2->setContext("Context 2");
        self::$em->persist($permission2);

        // user groups
        $group1 = new Group();
        $group1->setName('Group1');
        $group1->setLft(0);
        $group1->setRgt(0);
        $group1->setDepth(0);
        $group1->setCreated(new DateTime());
        $group1->setChanged(new DateTime());
        self::$em->persist($group1);

        $group2 = new Group();
        $group2->setName('Group2');
        $group2->setLft(0);
        $group2->setRgt(0);
        $group2->setDepth(0);
        $group2->setCreated(new DateTime());
        $group2->setChanged(new DateTime());
        self::$em->persist($group2);

        self::$em->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(

            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactLocale'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\FaxType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountContact'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\User'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\UserSetting'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\UserGroup'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Group'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\UserRole'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Role'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Permission'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\SecurityType'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag')
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    public function testLoadLockedUserByUsername()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/login_check',
            array(
                '_username' => 'admin',
                '_password'=> 'securepassword',
                '_target_path' => '/toptronic'
            ),
            array(),
            array(
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            )
    );
        $response = json_decode($client->getResponse()->getContent());

//        $this->setExpectedException('Symfony\Security\Core\Exception\UsernameNotFoundException');
//        $this->setExpectedException('Symfony\Security\Core\Exception\DisabledException');
//        $this->setExpectedException('Symfony\Security\Core\Exception\LockedException');

    }

    public function testFindBySystem()
    {
//        $client = static::createClient();

        // FIXME works when $this->getSystem() is set in user repository
//        $em = $client->getContainer()->get('sulu_security.user_repository_factory')->getManager();
//        /* @var UserRepository $repo */
//        $repo = $em->getRepository('Sulu\Bundle\SecurityBundle\Entity\User');
//        $employees = $repo->getUserInSystem();

        // FIXME alternative would be to get the container via the factory but there following in the repo is null $this->requestAnalyzer->getCurrentWebspace()
//        $repo = $client->getContainer()->get('sulu_security.user_repository_factory')->getRepository();
//
//        $this->assertEquals(1, count($employees));
//        $this->assertEquals('admin', $employees[0]->getUsername());
//        $this->assertEquals('1', $employees[0]->getId());
//        $this->assertEquals('Max', $employees[0]->getContact()->getFirstName());
//        $this->assertEquals('Muster', $employees[0]->getContact()->getLastName());
//
//        $employees = $repo->findAll();
//        $this->assertEquals(2, count($employees));
    }
}
