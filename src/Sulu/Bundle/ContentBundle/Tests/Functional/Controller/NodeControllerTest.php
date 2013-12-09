<?php

namespace Sulu\Bundle\ContentBundle\Tests\Controller;

use PHPCR\NodeInterface;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Sulu\Component\Testing\DatabaseTestCase;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\Tools\SchemaTool;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;

use DateTime;

class NodeControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var SchemaTool
     */
    protected static $tool;

    /**
     * @var SessionInterface
     */
    public $session;

    protected function setUp()
    {
        $this->setUpSchema();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setCreated(new DateTime());
        $contact->setChanged(new DateTime());
        self::$em->persist($contact);

        $emailType = new EmailType();
        $emailType->setName('Private');
        self::$em->persist($emailType);

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        self::$em->persist($email);
        self::$em->flush();

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setSystem('Sulu');
        $role1->setChanged(new DateTime());
        $role1->setCreated(new DateTime());
        self::$em->persist($role1);

        $user = new User();
        $user->setUsername('admin');
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact);
        self::$em->persist($user);
        self::$em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale(json_encode(array('de', 'en')));
        self::$em->persist($userRole1);

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($role1);
        $permission1->setContext("Context 1");
        self::$em->persist($permission1);
        self::$em->flush();

        $this->prepareSession();

        NodeHelper::purgeWorkspace($this->session);
        $this->session->save();

        $this->prepareRepository();
        $this->session->save();

        $cmf = $this->session->getRootNode()->addNode('cmf');
        $cmf->addNode('routes');
        $cmf->addNode('contents');

        $this->session->save();
    }

    private function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(

            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactLocale'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\User'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\UserRole'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Role'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Permission')
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    private function prepareSession()
    {
        $factoryclass = '\Jackalope\RepositoryFactoryJackrabbit';
        $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
        $factory = new $factoryclass();
        $repository = $factory->getRepository($parameters);
        $credentials = new \PHPCR\SimpleCredentials('admin', 'admin');
        $this->session = $repository->login($credentials, 'default');
    }

    public function prepareRepository()
    {
        $this->session->getWorkspace()->getNamespaceRegistry()->registerNamespace('sulu', 'http://sulu.io/phpcr');
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new SuluNodeType(), true);
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new PathNodeType(), true);
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new ContentNodeType(), true);
    }

    protected function tearDown()
    {
        if ($this->session != null) {
            NodeHelper::purgeWorkspace($this->session);
            $this->session->save();
        }
        parent::tearDown();
    }

    public function testPost()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/de/test',
            'article' => 'Test'
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $client->request('POST', '/api/nodes?template=overview', $data);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $item = $response->_embedded[0];

        $this->assertEquals('Testtitle', $item->title);
        $this->assertEquals('Test', $item->article);
        $this->assertEquals('/de/test', $item->url);
        $this->assertEquals(array('tag1', 'tag2'), $item->tags);
        $this->assertEquals('Max Mustermann', $item->creator);
        $this->assertEquals('Max Mustermann', $item->changer);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/routes/de/test');

        /** @var NodeInterface $content */
        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('title')->getString());
        $this->assertEquals('Test', $content->getProperty('article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('tags'));
        $this->assertEquals(1, $content->getPropertyValue('sulu:creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu:changer'));
    }

    private function beforeTestGet()
    {
        $data = array(
            array(
                'title' => 'test1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/test1',
                'article' => 'Test'
            ),
            array(
                'title' => 'test2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/test2',
                'article' => 'Test'
            )
        );

        /** @var ContentMapperInterface $mapper */
        $mapper = self::$kernel->getContainer()->get('sulu.content.mapper');

        for ($i = 0; $i < count($data); $i++) {
            $data[$i] = $mapper->save($data[$i], 'overview', 'default', 'en', 1)->toArray();
        }

        return $data;
    }

    public function testGet()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->beforeTestGet();

        $client->request('GET', '/api/nodes/' . $data[0]['id']);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($data[0]['title'], $response->title);
        $this->assertEquals($data[0]['tags'], $response->tags);
        $this->assertEquals($data[0]['url'], $response->url);
        $this->assertEquals($data[0]['article'], $response->article);
    }

    public function testPut()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->beforeTestGet();

        $data[0]['title'] = 'test123';
        $data[0]['tags'] = array('new tag');
        $data[0]['article'] = 'thats a new article';

        $client->request('PUT', '/api/nodes/' . $data[0]['id'] . '?template=overview', $data[0]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $item = $response->_embedded[0];

        $this->assertEquals($data[0]['title'], $item->title);
        $this->assertEquals($data[0]['tags'], $item->tags);
        $this->assertEquals($data[0]['url'], $item->url);
        $this->assertEquals($data[0]['article'], $item->article);
        $this->assertEquals('Max Mustermann', $item->creator);
        $this->assertEquals('Max Mustermann', $item->creator);

        $client->request('GET', '/api/nodes?depth=1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals(2, sizeof($response->_embedded));

        $this->assertEquals($data[1]['title'], $response->_embedded[0]->title);
        $this->assertEquals($data[1]['tags'], $response->_embedded[0]->tags);
        $this->assertEquals($data[1]['url'], $response->_embedded[0]->url);
        $this->assertEquals($data[1]['article'], $response->_embedded[0]->article);
        $this->assertEquals('Max Mustermann', $response->_embedded[0]->creator);
        $this->assertEquals('Max Mustermann', $response->_embedded[0]->creator);

        $this->assertEquals($data[0]['title'], $response->_embedded[1]->title);
        $this->assertEquals($data[0]['tags'], $response->_embedded[1]->tags);
        $this->assertEquals($data[0]['url'], $response->_embedded[1]->url);
        $this->assertEquals($data[0]['article'], $response->_embedded[1]->article);
        $this->assertEquals('Max Mustermann', $response->_embedded[1]->creator);
        $this->assertEquals('Max Mustermann', $response->_embedded[1]->creator);
    }

    private function beforeTestTreeGet()
    {
        $data = array(
            array(
                'title' => 'test1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/test1',
                'article' => 'Test'
            ),
            array(
                'title' => 'test2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/test2',
                'article' => 'Test'
            ),
            array(
                'title' => 'test3',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/test3',
                'article' => 'Test'
            ),
            array(
                'title' => 'test4',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/test4',
                'article' => 'Test'
            ),
            array(
                'title' => 'test5',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/test5',
                'article' => 'Test'
            )
        );

        /** @var ContentMapperInterface $mapper */
        $mapper = self::$kernel->getContainer()->get('sulu.content.mapper');

        $data[0] = $mapper->save($data[0], 'overview', 'default', 'en', 1)->toArray();
        $data[1] = $mapper->save($data[1], 'overview', 'default', 'en', 1)->toArray();
        $data[2] = $mapper->save($data[2], 'overview', 'default', 'en', 1, true, null, $data[1]['id'])->toArray();
        $data[3] = $mapper->save($data[3], 'overview', 'default', 'en', 1, true, null, $data[1]['id'])->toArray();
        $data[4] = $mapper->save($data[4], 'overview', 'default', 'en', 1, true, null, $data[3]['id'])->toArray();

        return $data;
    }

    public function testTreeGet()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->beforeTestTreeGet();

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded;

        $this->assertEquals(2, sizeof($items));
        $this->assertEquals($data[0]['title'], $items[0]->title);
        $this->assertEquals($data[1]['title'], $items[1]->title);

        // get subitems (remove /admin for test environment)
        $client->request('GET', str_replace('/admin', '', $items[1]->_links->children));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded;

        $this->assertEquals(2, sizeof($items));
        $this->assertEquals($data[2]['title'], $items[0]->title);
        $this->assertEquals($data[3]['title'], $items[1]->title);

        // get subitems (remove /admin for test environment)
        $client->request('GET', str_replace('/admin', '', $items[1]->_links->children));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded;

        $this->assertEquals(1, sizeof($items));
        $this->assertEquals($data[4]['title'], $items[0]->title);
    }
}
