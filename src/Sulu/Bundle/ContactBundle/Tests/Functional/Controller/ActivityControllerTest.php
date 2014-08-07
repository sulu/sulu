<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;
use Sulu\Bundle\ContactBundle\Entity\ActivityType;

class ActivityControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    public function setUp()
    {
        $this->setUpSchema();

        $account = new Account();
        $account->setName('Company');
        $account->setType(Account::TYPE_BASIC);
        $account->setDisabled(0);
        $account->setCreated(new \DateTime());
        $account->setChanged(new \DateTime());
        $account->setPlaceOfJurisdiction('Feldkirch');

        $emailType = new EmailType();
        $emailType->setName('Private');

        $email = new Email();
        $email->setEmail('office@company.example');
        $email->setEmailType($emailType);
        $account->addEmail($email);

        $contact = new Contact();
        $contact->setFirstName("Vorname");
        $contact->setLastName("Nachname");
        $contact->setMiddleName("Mittelname");
        $contact->setCreated(new \DateTime());
        $contact->setChanged(new \DateTime());
        $contact->setDisabled(0);
        $contact->setFormOfAddress(0);

        $email2 = new Email();
        $email2->setEmail('vorname.nachname@company.example');
        $email2->setEmailType($emailType);
        $contact->addEmail($email2);

        $activityType = new ActivityType();
        $activityType->setName('activityType');

        $activityState = new ActivityStatus();
        $activityState->setName('activityState');

        $activityPriortiy = new ActivityPriority();
        $activityPriortiy->setName('activityPriortiy');

        $activity = new Activity();
        $activity->setSubject('test');
        $activity->setNote('note');
        $activity->setDueDate(new \DateTime());
        $activity->setAssignedContact($contact);
        $activity->setAccount($account);
        $activity->setActivityType($activityType);
        $activity->setActivityPriority($activityPriortiy);
        $activity->setActivityStatus($activityState);
        $activity->setStartDate(new \DateTime());
        $activity->setCreated(new \DateTime());
        $activity->setChanged(new \DateTime());

        $activity2 = new Activity();
        $activity2->setSubject('test 2');
        $activity2->setNote('note 2');
        $activity2->setDueDate(new \DateTime());
        $activity2->setAssignedContact($contact);
        $activity2->setContact($contact);
        $activity2->setActivityType($activityType);
        $activity2->setActivityPriority($activityPriortiy);
        $activity2->setActivityStatus($activityState);
        $activity2->setStartDate(new \DateTime());
        $activity2->setCreated(new \DateTime());
        $activity2->setChanged(new \DateTime());

        self::$em->persist($activityType);
        self::$em->persist($activityState);
        self::$em->persist($activityPriortiy);
        self::$em->persist($activity);
        self::$em->persist($activity2);
        self::$em->persist($emailType);
        self::$em->persist($contact);
        self::$em->persist($account);
        self::$em->persist($email);
        self::$em->persist($email2);

        self::$em->flush();
    }

    public
    function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public
    function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata(
                'Sulu\Bundle\TestBundle\Entity\TestUser'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\AccountCategory'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\TestBundle\Entity\TestUser'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Account'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\ActivityStatus'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\ActivityPriority'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\ActivityType'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Activity'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Address'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\AccountAddress'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\ContactAddress'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\AddressType'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\BankAccount'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Contact'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\ContactLocale'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Country'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Email'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\EmailType'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Note'
            ),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\FaxType'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Phone'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\PhoneType'
            ),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\UrlType'
            ),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\AccountCategory'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\AccountContact'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\TermsOfPayment'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\Position'
            ),
            self::$em->getClassMetadata(
                'Sulu\Bundle\ContactBundle\Entity\ContactTitle'
            ),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation'),
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    private
    function createTestClient()
    {
        return $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    public
    function testGet()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            'api/activities'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = $response->_embedded->activities;

        $this->assertEquals(2, count($data));

        if ($data[0]->id === 1) {
            $this->assertEquals(1, $data[0]->id);
            $this->assertEquals('test', $data[0]->subject);
            $this->assertEquals('note', $data[0]->note);
            $this->assertNotEmpty($data[0]->dueDate);
            $this->assertNotEmpty($data[0]->startDate);
            $this->assertNotEmpty($data[0]->created);
            $this->assertNotEmpty($data[0]->changed);
            $this->assertEquals(1, $data[0]->activityStatus->id);
            $this->assertEquals(1, $data[0]->activityType->id);
            $this->assertEquals(1, $data[0]->activityPriority->id);
            $this->assertEquals(false, array_key_exists('contact', $data[0]));
            $this->assertEquals(1, $data[0]->account->id);
            $this->assertEquals(1, $data[0]->assignedContact->id);

            $this->assertEquals(2, $data[1]->id);
            $this->assertEquals('test 2', $data[1]->subject);
            $this->assertEquals('note 2', $data[1]->note);
            $this->assertNotEmpty($data[1]->dueDate);
            $this->assertNotEmpty($data[1]->startDate);
            $this->assertNotEmpty($data[1]->created);
            $this->assertNotEmpty($data[1]->changed);
            $this->assertEquals(1, $data[1]->activityStatus->id);
            $this->assertEquals(1, $data[1]->activityType->id);
            $this->assertEquals(1, $data[1]->activityPriority->id);
            $this->assertEquals(1, $data[1]->contact->id);
            $this->assertEquals(false, array_key_exists('account', $data[1]));
            $this->assertEquals(1, $data[1]->assignedContact->id);
        } else {
            $this->assertEquals(1, $data[1]->id);
            $this->assertEquals('test', $data[1]->subject);
            $this->assertEquals('note', $data[1]->note);
            $this->assertNotEmpty($data[1]->dueDate);
            $this->assertNotEmpty($data[1]->startDate);
            $this->assertNotEmpty($data[1]->created);
            $this->assertNotEmpty($data[1]->changed);
            $this->assertEquals(1, $data[1]->activityStatus->id);
            $this->assertEquals(1, $data[1]->activityType->id);
            $this->assertEquals(1, $data[1]->activityPriority->id);
            $this->assertEquals(false, array_key_exists('contact', $data[1]));
            $this->assertEquals(1, $data[1]->account->id);
            $this->assertEquals(1, $data[1]->assignedContact->id);

            $this->assertEquals(2, $data[0]->id);
            $this->assertEquals('test 2', $data[0]->subject);
            $this->assertEquals('note 2', $data[0]->note);
            $this->assertNotEmpty($data[0]->dueDate);
            $this->assertNotEmpty($data[0]->startDate);
            $this->assertNotEmpty($data[0]->created);
            $this->assertNotEmpty($data[0]->changed);
            $this->assertEquals(1, $data[0]->activityStatus->id);
            $this->assertEquals(1, $data[0]->activityType->id);
            $this->assertEquals(1, $data[0]->activityPriority->id);
            $this->assertEquals(1, $data[0]->assignedContact->id);
        }
    }

    public
    function testGetFlatByAccount()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            'api/activities?flat=true&account=1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = $response->_embedded->activities;
        $this->assertEquals(1, $response->total);

        $this->assertEquals(1, $data[0]->id);
        $this->assertEquals('test', $data[0]->subject);
        $this->assertEquals('note', $data[0]->note);
        $this->assertNotEmpty($data[0]->dueDate);
        $this->assertNotEmpty($data[0]->startDate);
        $this->assertNotEmpty($data[0]->created);
        $this->assertNotEmpty($data[0]->changed);
        $this->assertEquals('activityState', $data[0]->activityStatus);
        $this->assertEquals('activityType', $data[0]->activityType);
        $this->assertEquals('activityPriortiy', $data[0]->activityPriority);
        $this->assertEquals('Vorname Nachname', $data[0]->assignedContact);
    }

    public
    function testGetFlatByContact()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            'api/activities?flat=true&contact=1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = $response->_embedded->activities;

        $this->assertEquals(1, $response->total);

        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('test 2', $data[0]->subject);
        $this->assertEquals('note 2', $data[0]->note);
        $this->assertNotEmpty($data[0]->dueDate);
        $this->assertNotEmpty($data[0]->startDate);
        $this->assertNotEmpty($data[0]->created);
        $this->assertNotEmpty($data[0]->changed);
        $this->assertEquals('activityState', $data[0]->activityStatus);
        $this->assertEquals('activityType', $data[0]->activityType);
        $this->assertEquals('activityPriortiy', $data[0]->activityPriority);
        $this->assertEquals('Vorname Nachname', $data[0]->assignedContact);
    }

    public
    function testPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => 1
                ),
                'activityStatus' => array(
                    'id' => 1
                ),
                'activityType' => array(
                    'id' => 1
                ),
                'activityPriority' => array(
                    'id' => 1
                ),
                'assignedContact' => array(
                    'id' => 1
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(3, $response->id);
        $this->assertEquals('test 3', $response->subject);
        $this->assertEquals('note 3', $response->note);
        $this->assertNotEmpty($response->dueDate);
        $this->assertNotEmpty($response->created);
        $this->assertNotEmpty($response->changed);
        $this->assertEquals(1, $response->activityStatus->id);
        $this->assertEquals(1, $response->activityType->id);
        $this->assertEquals(1, $response->activityPriority->id);
        $this->assertEquals(false, array_key_exists('account', $response));
        $this->assertEquals(1, $response->contact->id);
        $this->assertEquals(1, $response->assignedContact->id);
    }

    public
    function testPostInValidContact()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => 99
                ),
                'activityStatus' => array(
                    'id' => 1
                ),
                'activityType' => array(
                    'id' => 1
                ),
                'activityPriority' => array(
                    'id' => 1
                ),
                'assignedContact' => array(
                    'id' => 1
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public
    function testPostInValidAccount()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'account' => array(
                    'id' => 99
                ),
                'activityStatus' => array(
                    'id' => 1
                ),
                'activityType' => array(
                    'id' => 1
                ),
                'activityPriority' => array(
                    'id' => 1
                ),
                'assignedContact' => array(
                    'id' => 1
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public
    function testPostInValidAssignedContact()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => 1
                ),
                'activityStatus' => array(
                    'id' => 1
                ),
                'activityType' => array(
                    'id' => 1
                ),
                'activityPriority' => array(
                    'id' => 1
                ),
                'assignedContact' => array(
                    'id' => 99
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public
    function testPostMissingSubject()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => 1
                ),
                'activityStatus' => array(
                    'id' => 1
                ),
                'activityType' => array(
                    'id' => 1
                ),
                'activityPriority' => array(
                    'id' => 1
                ),
                'assignedContact' => array(
                    'id' => 1
                ),
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public
    function testPut()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            'api/activities/1',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => 1
                ),
                'activityStatus' => array(
                    'id' => 1
                ),
                'activityType' => array(
                    'id' => 1
                ),
                'activityPriority' => array(
                    'id' => 1
                ),
                'assignedContact' => array(
                    'id' => 1
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(1, $response->id);
        $this->assertEquals('test 3', $response->subject);
        $this->assertEquals('note 3', $response->note);
        $this->assertNotEmpty($response->dueDate);
        $this->assertNotEmpty($response->created);
        $this->assertNotEmpty($response->changed);
        $this->assertEquals(1, $response->activityStatus->id);
        $this->assertEquals(1, $response->activityType->id);
        $this->assertEquals(1, $response->activityPriority->id);
        $this->assertEquals(false, array_key_exists('account', $response));
        $this->assertEquals(1, $response->contact->id);
        $this->assertEquals(1, $response->assignedContact->id);
    }

    public
    function testPutInvalidId()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            'api/activities/100',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => 1
                ),
                'activityStatus' => array(
                    'id' => 1
                ),
                'activityType' => array(
                    'id' => 1
                ),
                'activityPriority' => array(
                    'id' => 1
                ),
                'assignedContact' => array(
                    'id' => 1
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public
    function testDelete()
    {
        $client = $this->createTestClient();

        $client->request(
            'DELETE',
            'api/activities/1'
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client = $this->createTestClient();
        $client->request(
            'GET',
            'api/activities'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, count($response->_embedded->activities));
    }

    public
    function testDeleteInvalidId()
    {
        $client = $this->createTestClient();

        $client->request(
            'DELETE',
            'api/activities/100'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $client = $this->createTestClient();
        $client->request(
            'GET',
            'api/activities'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(2, count($response->_embedded->activities));
    }

}
