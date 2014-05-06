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


use DateTime;
use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
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
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

class AccountControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var AccountCategory
     */
    protected static $category;

    /**
     * @var AccountCategory
     */
    protected static $category2;

    public function setUp()
    {
        $this->setUpSchema();

        self::$category = new AccountCategory();
        self::$category->setCategory('Hauptsitz');

        self::$category2 = new AccountCategory();
        self::$category2->setCategory('Nebensitz');

        self::$em->persist(self::$category);
        self::$em->persist(self::$category2);

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
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountCategory'),
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    private function createTestClient() {
        return $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    public function testGetAll()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            'api/account/categories'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Hauptsitz', $response->_embedded[0]->category);
        $this->assertEquals(1, $response->_embedded[0]->id);

        $this->assertEquals('Nebensitz', $response->_embedded[1]->category);
        $this->assertEquals(2, $response->_embedded[1]->id);
    }

    public function testPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            'api/accounts/categories',
            array(
                'category' => 'Nebensitz 2',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Nebensitz 2', $response->category);
        $this->assertEquals(3, $response->id);

        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals('Hauptsitz', $response2->_embedded[0]->category);
        $this->assertEquals(1, $response2->_embedded[0]->id);

        $this->assertEquals('Nebensitz', $response2->_embedded[1]->category);
        $this->assertEquals(2, $response2->_embedded[1]->id);

        $this->assertEquals('Nebensitz 2', $response2->_embedded[2]->category);
        $this->assertEquals(3, $response2->_embedded[2]->id);

    }

    public function testPostInvalid()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            'api/accounts/categories',
            array(
                'category' => '',
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

}
