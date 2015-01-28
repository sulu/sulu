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

use Doctrine\ORM\EntityManager;

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Activity;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus;
use Sulu\Bundle\ContactBundle\Entity\ActivityType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ActivityControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var EmailType
     */
    private $emailType;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var Email
     */
    private $email2;

    /**
     * @var ActivityType
     */
    private $activityType;

    /**
     * @var ActivityStatus
     */
    private $activityStatus;

    /**
     * @var ActivityPriority
     */
    private $activityPriority;

    /**
     * @var Activity
     */
    private $activity;

    /**
     * @var Activity
     */
    private $activity2;

    public function setUp()
    {
        $this->purgeDatabase();
        $this->em = $this->db('ORM')->getOm();
        $this->initOrm();
    }

    private function initOrm()
    {
        $account = new Account();
        $account->setName('Company');
        $account->setType(Account::TYPE_BASIC);
        $account->setDisabled(0);
        $account->setCreated(new \DateTime());
        $account->setChanged(new \DateTime());
        $account->setPlaceOfJurisdiction('Feldkirch');

        $this->account = $account;

        $emailType = new EmailType();
        $emailType->setName('Private');

        $this->emailType = $emailType;

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

        $this->contact = $contact;

        $email2 = new Email();
        $email2->setEmail('vorname.nachname@company.example');
        $email2->setEmailType($emailType);
        $contact->addEmail($email2);

        $this->email2 = $email2;

        $activityType = new ActivityType();
        $activityType->setName('activityType');

        $this->activityType = $activityType;

        $activityState = new ActivityStatus();
        $activityState->setName('activityState');

        $this->activityStatus = $activityState;

        $activityPriortiy = new ActivityPriority();
        $activityPriortiy->setName('activityPriortiy');

        $this->activityPriority = $activityPriortiy;

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

        $this->activity = $activity;

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

        $this->activity2 = $activity2;

        $this->em->persist($activityType);
        $this->em->persist($activityState);
        $this->em->persist($activityPriortiy);
        $this->em->persist($activity);
        $this->em->persist($activity2);
        $this->em->persist($emailType);
        $this->em->persist($contact);
        $this->em->persist($account);
        $this->em->persist($email);
        $this->em->persist($email2);

        $this->em->flush();
    }

    public function testGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            'api/activities'
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = $response['_embedded']['activities'];

        $this->assertEquals(2, count($data));

        //FIXME: This prevents wrong order of data (which happens in some test-runs)
        if (array_key_exists('contact', $data[0])) {
            // order has changed, switch data-sets
            $tmp = $data[0];
            $data[0] = $data[1];
            $data[1] = $tmp;
        }
        // check if fields are present
        $this->assertNotNull($data[0]['id']);
        $this->assertNotEmpty($data[0]['dueDate']);
        $this->assertNotEmpty($data[0]['startDate']);
        $this->assertNotEmpty($data[0]['created']);
        $this->assertNotEmpty($data[0]['changed']);
        $this->assertArrayNotHasKey('contact', $data[0]);

        $this->assertNotNull($data[1]['id']);
        $this->assertNotEmpty($data[1]['dueDate']);
        $this->assertNotEmpty($data[1]['startDate']);
        $this->assertNotEmpty($data[1]['created']);
        $this->assertNotEmpty($data[1]['changed']);
        $this->assertArrayHasKey('contact', $data[1]);

        // filter data and check only checkable fields
        $filterKeys = array(
            'subject',
            'note',
            'activityStatus',
            'activityType',
            'activityPriority',
            'account',
            'assignedContact'
        );

        $filterKeyArrays = array(
            'activityStatus',
            'activityType',
            'activityPriority',
            'account',
            'assignedContact'
        );

        $filteredData = array_map(
            function ($value) use ($filterKeys, $filterKeyArrays) {
                foreach ($filterKeyArrays as $filterKey) {
                    if (array_key_exists($filterKey, $value)) {
                        $value[$filterKey] = array_intersect_key($value[$filterKey], array_flip(array('id')));
                    }
                }

                return array_intersect_key($value, array_flip($filterKeys));
            },
            $data
        );

        $this->assertContains(
            array(
                'subject' => 'test',
                'note' => 'note',
                'activityStatus' => array(
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'account' => array(
                    'id' => $this->account->getId()
                ),
                'assignedContact' => array(
                    'id' => $this->contact->getId()
                )
            ),
            $filteredData
        );

        $this->assertContains(
            array(
                'subject' => 'test 2',
                'note' => 'note 2',
                'activityStatus' => array(
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'assignedContact' => array(
                    'id' => $this->contact->getId()
                )
            ),
            $filteredData
        );
    }

    public function testGetFlatByAccount()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            'api/activities?flat=true&account=' . $this->account->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = $response->_embedded->activities;
        $this->assertEquals(1, $response->total);

        $this->assertEquals($this->activity->getId(), $data[0]->id);
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

    public function testGetFlatByContact()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            'api/activities?flat=true&contact=' . $this->contact->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = $response->_embedded->activities;

        $this->assertEquals(1, $response->total);

        $this->assertNotNull($data[0]->id);
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

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => $this->contact->getId()
                ),
                'activityStatus' => array(
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'assignedContact' => array(
                    'id' => $this->contact->getId(),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotNull($response->id);
        $this->assertEquals('test 3', $response->subject);
        $this->assertEquals('note 3', $response->note);
        $this->assertNotEmpty($response->dueDate);
        $this->assertNotEmpty($response->created);
        $this->assertNotEmpty($response->changed);
        $this->assertEquals($this->activityStatus->getId(), $response->activityStatus->id);
        $this->assertEquals($this->activityType->getId(), $response->activityType->id);
        $this->assertEquals($this->activityPriority->getId(), $response->activityPriority->id);
        $this->assertEquals(false, array_key_exists('account', $response));
        $this->assertEquals($this->contact->getId(), $response->contact->id);
        $this->assertEquals($this->contact->getId(), $response->assignedContact->id);
    }

    public function testPostInValidContact()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => 99123123
                ),
                'activityStatus' => array(
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'assignedContact' => array(
                    'id' => $this->contact->getId(),
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testPostInValidAccount()
    {
        $client = $this->createAuthenticatedClient();

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
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'assignedContact' => array(
                    'id' => $this->contact->getId(),
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testPostInValidAssignedContact()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => $this->contact->getId(),
                ),
                'activityStatus' => array(
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'assignedContact' => array(
                    'id' => 123123
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testPostMissingSubject()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            'api/activities',
            array(
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => $this->contact->getId(),
                ),
                'activityStatus' => array(
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'assignedContact' => array(
                    'id' => $this->contact->getId(),
                ),
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            'api/activities/' . $this->activity->getId(),
            array(
                'subject' => 'test 3',
                'note' => 'note 3',
                'dueDate' => '1-1-2013',
                'contact' => array(
                    'id' => $this->contact->getId(),
                ),
                'activityStatus' => array(
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'assignedContact' => array(
                    'id' => $this->contact->getId(),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotNull($response->id);
        $this->assertEquals('test 3', $response->subject);
        $this->assertEquals('note 3', $response->note);
        $this->assertNotEmpty($response->dueDate);
        $this->assertNotEmpty($response->created);
        $this->assertNotEmpty($response->changed);
        $this->assertNotNull($response->activityStatus->id);
        $this->assertNotNull($response->activityType->id);
        $this->assertNotNull($response->activityPriority->id);
        $this->assertEquals(false, array_key_exists('account', $response));
        $this->assertNotNull($response->contact->id);
        $this->assertNotNull($response->assignedContact->id);
    }

    public function testPutInvalidId()
    {
        $client = $this->createAuthenticatedClient();

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
                    'id' => $this->activityStatus->getId(),
                ),
                'activityType' => array(
                    'id' => $this->activityType->getId(),
                ),
                'activityPriority' => array(
                    'id' => $this->activityPriority->getId(),
                ),
                'assignedContact' => array(
                    'id' => 1
                ),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            'api/activities/' . $this->activity->getId()
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            'api/activities'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, count($response->_embedded->activities));
    }

    public function testDeleteInvalidId()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            'api/activities/666'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            'api/activities'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(2, count($response->_embedded->activities));
    }
}
