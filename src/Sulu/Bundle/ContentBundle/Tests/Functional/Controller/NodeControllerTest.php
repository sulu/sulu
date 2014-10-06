<?php

namespace Sulu\Bundle\ContentBundle\Tests\Controller;

use PHPCR\NodeInterface;
use PHPCR\SimpleCredentials;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;

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
        self::$em->flush();

        $emailType = new EmailType();
        $emailType->setName('Private');
        self::$em->persist($emailType);
        self::$em->flush();

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
        self::$em->flush();

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
        self::$em->flush();

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($role1);
        $permission1->setContext("Context 1");
        self::$em->persist($permission1);
        self::$em->flush();

        $tag1 = new Tag();
        $tag1->setChanged(new DateTime());
        $tag1->setCreated(new DateTime());
        $tag1->setName('tag1');
        self::$em->persist($tag1);
        self::$em->flush();

        $tag2 = new Tag();
        $tag2->setChanged(new DateTime());
        $tag2->setCreated(new DateTime());
        $tag2->setName('tag2');
        self::$em->persist($tag2);
        self::$em->flush();

        $tag3 = new Tag();
        $tag3->setChanged(new DateTime());
        $tag3->setCreated(new DateTime());
        $tag3->setName('tag3');
        self::$em->persist($tag3);
        self::$em->flush();

        $tag4 = new Tag();
        $tag4->setChanged(new DateTime());
        $tag4->setCreated(new DateTime());
        $tag4->setName('tag4');
        self::$em->persist($tag4);
        self::$em->flush();

        $this->prepareSession();

        NodeHelper::purgeWorkspace($this->session);
        $this->session->save();

        $this->prepareRepository();
        $this->session->save();

        $cmf = $this->session->getRootNode()->addNode('cmf');
        $webspace = $cmf->addNode('sulu_io');
        $nodes = $webspace->addNode('routes');
        $nodes->addNode('de');
        $nodes->addNode('en');
        $content = $webspace->addNode('contents');
        $content->setProperty('i18n:en-template', 'default');
        $content->setProperty('i18n:en-creator', 1);
        $content->setProperty('i18n:en-created', new \DateTime());
        $content->setProperty('i18n:en-changer', 1);
        $content->setProperty('i18n:en-changed', new \DateTime());
        $content->addMixin('sulu:content');

        $this->session->save();
    }

    public function prepareRepository()
    {
        $this->session->getWorkspace()->getNamespaceRegistry()->registerNamespace('sulu', 'http://sulu.io/phpcr');
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new SuluNodeType(), true);
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new PathNodeType(), true);
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new ContentNodeType(), true);
        $this->session->getWorkspace()->getNamespaceRegistry()->registerNamespace('sulu', 'http://sulu.io/phpcr');
        $this->session->getWorkspace()->getNamespaceRegistry()->registerNamespace(
            'i18n',
            'http://sulu.io/phpcr/locale'
        );
    }

    private function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactLocale'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\BankAccount'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\FaxType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactTitle'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfPayment'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountCategory'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\User'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\UserRole'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Role'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Permission'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\SecurityType'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Collection'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Media'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\MediaType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\File'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersion'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category')
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
        $credentials = new SimpleCredentials('admin', 'admin');
        $this->session = $repository->login($credentials, 'test');
    }

    protected function tearDown()
    {
        if ($this->session != null) {
            NodeHelper::purgeWorkspace($this->session);
            $this->session->save();
        }
        self::$tool->dropSchema(self::$entities);
        parent::tearDown();
    }

    public function providePost()
    {
        return array(
            array(
                array(
                    'template' => 'default',
                    'webspace' => 'sulu_io',
                    'language' => 'en',
                ),
            ),
        //    array(
        //        array(
        //            'template' => 'hotel',
        //            'webspace' => 'sulu_io',
        //            'language' => 'en',
        //            'type' => 'snippet',
        //        ),
        //    ),
        );
    }

    /**
     * @dataProvider providePost
     */
    public function testPost($params)
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/test',
            'article' => 'Test'
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );

        $params = http_build_query($params);

        $client->request('POST', '/api/nodes?' . $params, $data);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Testtitle', $response->title);
        $this->assertEquals('Test', $response->article);
        $this->assertEquals('/test', $response->url);
        $this->assertEquals(array('tag1', 'tag2'), $response->tags);
        $this->assertEquals(1, $response->creator);
        $this->assertEquals(1, $response->changer);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/en/test');

        /** @var NodeInterface $content */
        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('i18n:en-title')->getString());
        $this->assertEquals('Test', $content->getProperty('i18n:en-article')->getString());
        $this->assertEquals(array(1, 2), $content->getPropertyValue('i18n:en-tags'));
        $this->assertEquals(1, $content->getPropertyValue('i18n:en-creator'));
        $this->assertEquals(1, $content->getPropertyValue('i18n:en-changer'));
    }

    public function testPostTree()
    {
        $data1 = array(
            'title' => 'news',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news',
            'article' => 'Test'
        );
        $data2 = array(
            'title' => 'test-1',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'Test'
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data1);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $uuid = $response->id;

        $client->request(
            'POST',
            '/api/nodes?template=default&parent=' . $uuid . '&webspace=sulu_io&language=en',
            $data2
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('test-1', $response->title);
        $this->assertEquals('Test', $response->article);
        $this->assertEquals('/news/test', $response->url);
        $this->assertEquals(array('tag1', 'tag2'), $response->tags);
        $this->assertEquals(1, $response->creator);
        $this->assertEquals(1, $response->changer);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/en/news/test');

        /** @var NodeInterface $content */
        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('test-1', $content->getProperty('i18n:en-title')->getString());
        $this->assertEquals('Test', $content->getProperty('i18n:en-article')->getString());
        $this->assertEquals(array(1, 2), $content->getPropertyValue('i18n:en-tags'));
        $this->assertEquals(1, $content->getPropertyValue('i18n:en-creator'));
        $this->assertEquals(1, $content->getPropertyValue('i18n:en-changer'));

        // check parent
        $this->assertEquals($uuid, $content->getParent()->getIdentifier());
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

        $mapper->saveStartPage(array('title' => 'Start Page'), 'default', 'sulu_io', 'de', 1);

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );

        for ($i = 0; $i < count($data); $i++) {
            $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[$i]);
            $data[$i] = (array)json_decode($client->getResponse()->getContent(), true);
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

        $client->request('GET', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data[0]['title'], $response['title']);
        $this->assertEquals($data[0]['path'], $response['path']);
        $this->assertEquals($data[0]['tags'], $response['tags']);
        $this->assertEquals($data[0]['url'], $response['url']);
        $this->assertEquals($data[0]['article'], $response['article']);
    }

    public function testDelete()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->beforeTestGet();

        $client->request('DELETE', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
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

        $client->request(
            'PUT',
            '/api/nodes/' . $data[0]['id'] . '?template=default&webspace=sulu_io&language=en',
            $data[0]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($data[0]['title'], $response->title);
        $this->assertEquals($data[0]['tags'], $response->tags);
        $this->assertEquals($data[0]['url'], $response->url);
        $this->assertEquals($data[0]['article'], $response->article);
        $this->assertEquals(1, $response->creator);
        $this->assertEquals(1, $response->creator);

        $this->assertEquals(2, sizeof((array)$response->ext));

        $this->assertEquals(6, sizeof((array)$response->ext->seo));
        $this->assertEquals(7, sizeof((array)$response->ext->excerpt));

        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals(2, sizeof($response->_embedded->nodes));

        $this->assertEquals($data[1]['title'], $response->_embedded->nodes[0]->title);
        $this->assertEquals($data[0]['title'], $response->_embedded->nodes[1]->title);
    }

    private function buildTree()
    {
        $data = array(
            array(
                'title' => 'test1',
                'url' => '/test1',
                'article' => 'Test',
                'ext' => array(
                    'excerpt' => array(
                        'tags' => array(
                            'tag1'
                        )
                    )
                )
            ),
            array(
                'title' => 'test2',
                'url' => '/test2',
                'article' => 'Test',
                'ext' => array(
                    'excerpt' => array(
                        'tags' => array(
                            'tag2'
                        )
                    )
                )
            ),
            array(
                'title' => 'test3',
                'url' => '/test3',
                'article' => 'Test',
                'ext' => array(
                    'excerpt' => array(
                        'tags' => array(
                            'tag1',
                            'tag2'
                        )
                    )
                )
            ),
            array(
                'title' => 'test4',
                'url' => '/test4',
                'article' => 'Test',
                'ext' => array(
                    'excerpt' => array(
                        'tags' => array(
                            'tag1'
                        )
                    )
                )
            ),
            array(
                'title' => 'test5',
                'url' => '/test5',
                'article' => 'Test',
                'ext' => array(
                    'excerpt' => array(
                        'tags' => array(
                            'tag1',
                            'tag2'
                        )
                    )
                )
            )
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[0]);
        $data[0] = (array)json_decode($client->getResponse()->getContent(), true);

        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[1]);
        $data[1] = (array)json_decode($client->getResponse()->getContent(), true);
        $client->request(
            'POST',
            '/api/nodes?template=default&webspace=sulu_io&language=en&parent=' . $data[1]['id'],
            $data[2]
        );
        $data[2] = (array)json_decode($client->getResponse()->getContent(), true);
        $client->request(
            'POST',
            '/api/nodes?template=default&webspace=sulu_io&language=en&parent=' . $data[1]['id'],
            $data[3]
        );
        $data[3] = (array)json_decode($client->getResponse()->getContent(), true);
        $client->request(
            'POST',
            '/api/nodes?template=default&webspace=sulu_io&language=en&parent=' . $data[3]['id'],
            $data[4]
        );
        $data[4] = (array)json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'PUT',
            '/api/nodes/' . $data[0]['id'] . '?state=2&template=default&webspace=sulu_io&language=en',
            $data[0]
        );
        $data[0] = (array)json_decode($client->getResponse()->getContent(), true);
        $client->request(
            'PUT',
            '/api/nodes/' . $data[1]['id'] . '?state=2&template=default&webspace=sulu_io&language=en',
            $data[1]
        );
        $data[1] = (array)json_decode($client->getResponse()->getContent(), true);
        $client->request(
            'PUT',
            '/api/nodes/' . $data[2]['id'] . '?state=2&template=default&webspace=sulu_io&language=en',
            $data[2]
        );
        $data[2] = (array)json_decode($client->getResponse()->getContent(), true);
        $client->request(
            'PUT',
            '/api/nodes/' . $data[3]['id'] . '?state=2&template=default&webspace=sulu_io&language=en',
            $data[3]
        );
        $data[3] = (array)json_decode($client->getResponse()->getContent(), true);
        $client->request(
            'PUT',
            '/api/nodes/' . $data[4]['id'] . '?state=2&template=default&webspace=sulu_io&language=en',
            $data[4]
        );
        $data[4] = (array)json_decode($client->getResponse()->getContent(), true);

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
        $data = $this->buildTree();

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, sizeof($items));
        $this->assertEquals($data[0]['title'], $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals($data[1]['title'], $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        // get subitems (remove /admin for test environment)
        $client->request('GET', str_replace('/admin', '', $items[1]->_links->children));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, sizeof($items));
        $this->assertEquals($data[2]['title'], $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals($data[3]['title'], $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        // get subitems (remove /admin for test environment)
        $client->request('GET', str_replace('/admin', '', $items[1]->_links->children));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(1, sizeof($items));
        $this->assertEquals($data[4]['title'], $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
    }

    public function testGetFlat()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, sizeof($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=2&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(4, sizeof($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        $this->assertEquals('test3', $items[2]->title);
        $this->assertFalse($items[2]->hasSub);

        $this->assertEquals('test4', $items[3]->title);
        $this->assertTrue($items[3]->hasSub);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=3&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(5, sizeof($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        $this->assertEquals('test3', $items[2]->title);
        $this->assertFalse($items[2]->hasSub);

        $this->assertEquals('test4', $items[3]->title);
        $this->assertTrue($items[3]->hasSub);

        $this->assertEquals('test5', $items[4]->title);
        $this->assertFalse($items[4]->hasSub);

        // get child nodes from subNode
        $client->request('GET', '/api/nodes?depth=3&webspace=sulu_io&language=en&parent=' . $data[3]['id']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(1, sizeof($items));

        $this->assertEquals('test5', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
    }

    public function testGetTree()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&flat=false&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, sizeof($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, sizeof($items[0]->_embedded->nodes));

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(0, sizeof($items[1]->_embedded->nodes));

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=2&flat=false&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, sizeof($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, sizeof($items[0]->_embedded->nodes));

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(2, sizeof($items[1]->_embedded->nodes));

        $items = $items[1]->_embedded->nodes;

        $this->assertEquals('test3', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, sizeof($items[0]->_embedded->nodes));

        $this->assertEquals('test4', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(0, sizeof($items[1]->_embedded->nodes));

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=3&flat=false&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, sizeof($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, sizeof($items[0]->_embedded->nodes));

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(2, sizeof($items[1]->_embedded->nodes));

        $items = $items[1]->_embedded->nodes;

        $this->assertEquals('test3', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, sizeof($items[0]->_embedded->nodes));

        $this->assertEquals('test4', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(1, sizeof($items[1]->_embedded->nodes));

        $items = $items[1]->_embedded->nodes;

        $this->assertEquals('test5', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, sizeof($items[0]->_embedded->nodes));

        // get child nodes from subNode
        $client->request('GET', '/api/nodes?depth=3&flat=false&webspace=sulu_io&language=en&parent=' . $data[3]['id']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(1, sizeof($items));

        $this->assertEquals('test5', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, sizeof($items[0]->_embedded->nodes));
    }

    public function testSmartContent()
    {
        $data = $this->buildTree();

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en');
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals('', $response->title);
        $this->assertEquals(5, sizeof($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&dataSource=' . $data[1]['id']);
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, sizeof($items));
        $this->assertEquals($data[1]['title'], $response->title);

        $client->request(
            'GET',
            '/api/nodes/filter?webspace=sulu_io&language=en&dataSource=' . $data[1]['id'] . '&includeSubFolders=true'
        );
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(3, sizeof($items));
        $this->assertEquals($data[1]['title'], $response->title);

        $client->request(
            'GET',
            '/api/nodes/filter?webspace=sulu_io&language=en&dataSource=' . $data[1]['id'] . '&includeSubFolders=true&limitResult=2'
        );
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, sizeof($items));
        $this->assertEquals($data[1]['title'], $response->title);

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag1');
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals('', $response->title);
        $this->assertEquals(4, sizeof($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag2');
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals('', $response->title);
        $this->assertEquals(3, sizeof($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag1,tag2');
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals('', $response->title);
        $this->assertEquals(2, sizeof($items));

        $client->request(
            'GET',
            '/api/nodes/filter?webspace=sulu_io&language=en&dataSource=' . $data[1]['id'] . '&includeSubFolders=true&limitResult=2&sortBy=title'
        );
        $response = json_decode($client->getResponse()->getContent());
    }

    public function testBreadcrumb()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();
        $mapper = self::$kernel->getContainer()->get('sulu.content.mapper');
        $mapper->saveStartPage(array('title' => 'Start Page'), 'default', 'sulu_io', 'en', 1);

        $client->request('GET', '/api/nodes/' . $data[4]['id'] . '?breadcrumb=true&webspace=sulu_io&language=en');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data[4]['title'], $response['title']);
        $this->assertEquals($data[4]['url'], $response['url']);
        $this->assertEquals($data[4]['article'], $response['article']);

        $this->assertEquals(3, sizeof($response['breadcrumb']));
        $this->assertEquals('Start Page', $response['breadcrumb'][0]['title']);
        $this->assertEquals('test2', $response['breadcrumb'][1]['title']);
        $this->assertEquals('test4', $response['breadcrumb'][2]['title']);
    }

    public function testSmallResponse()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->beforeTestGet();

        $client->request('GET', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&complete=false');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('title', $response);
        $this->assertArrayNotHasKey('article', $response);
        $this->assertArrayNotHasKey('tags', $response);
        $this->assertArrayNotHasKey('ext', $response);
        $this->assertArrayNotHasKey('enabledShadowLanguage', $response);
        $this->assertArrayNotHasKey('concreteLanguages', $response);
        $this->assertArrayNotHasKey('shadowOn', $response);
        $this->assertArrayNotHasKey('shadowBaseLanguage', $response);
    }

    public function testCgetAction()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals(2, sizeof($items));

        $this->assertEquals(12, sizeof($items[0]));
        $this->assertArrayHasKey('id', $items[0]);
        $this->assertEquals('test1', $items[0]['title']);
        $this->assertEquals('/test1', $items[0]['path']);
        $this->assertEquals(2, $items[0]['nodeState']);
        $this->assertTrue($items[0]['publishedState']);
        $this->assertEmpty($items[0]['navContexts']);
        $this->assertFalse($items[0]['hasSub']);
        $this->assertEquals(0, sizeof($items[0]['_embedded']['nodes']));
        $this->assertArrayHasKey('_links', $items[0]);

        $this->assertEquals(12, sizeof($items[1]));
        $this->assertArrayHasKey('id', $items[1]);
        $this->assertEquals('test2', $items[1]['title']);
        $this->assertEquals('/test2', $items[1]['path']);
        $this->assertEquals(2, $items[1]['nodeState']);
        $this->assertTrue($items[1]['publishedState']);
        $this->assertEmpty($items[1]['navContexts']);
        $this->assertTrue($items[1]['hasSub']);
        $this->assertEquals(0, sizeof($items[1]['_embedded']['nodes']));
        $this->assertArrayHasKey('_links', $items[1]);
    }

    public function testHistory()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = array(
            'title' => 'news',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/a1',
            'article' => 'Test'
        );
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);
        $uuid = $response['id'];
        $data = array(
            'title' => 'news',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/a2',
            'article' => 'Test'
        );

        sleep(1);

        $client->request('PUT', '/api/nodes/' . $uuid . '?template=default&webspace=sulu_io&language=en', $data);
        $data = array(
            'title' => 'news',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/a3',
            'article' => 'Test'
        );
        $client->request('PUT', '/api/nodes/' . $uuid . '?template=default&webspace=sulu_io&language=en', $data);

        $client->request(
            'GET',
            '/api/nodes/' . $uuid . '/resourcelocators?template=default&webspace=sulu_io&language=en'
        );
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('/a2', $response['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/a1', $response['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testMove()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=move&destination=' . $data[1]['id']
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[0]['id'], $response['id']);
        $this->assertEquals('test1', $response['title']);
        $this->assertEquals('/test2/test1', $response['path']);
        $this->assertEquals('/test2/test1', $response['url']);
    }

    public function testMoveNonExistingSource()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        $client->request(
            'POST',
            '/api/nodes/123-123?webspace=sulu_io&language=en&action=move&destination=' . $data[1]['id']
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testMoveNonExistingDestination()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=move&destination=123-123'
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testCopy()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=copy&destination=' . $data[1]['id']
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertNotEquals($data[0]['id'], $response['id']);
        $this->assertEquals('test1', $response['title']);
        $this->assertEquals('/test2/test1', $response['path']);
        $this->assertEquals('/test2/test1', $response['url']);

        // check old node
        $client->request(
            'GET',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en'
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        // remove extension unececary for this test
        unset($data[0]['ext']);
        unset($data[0]['tags']);
        unset($response['ext']);
        unset($response['tags']);

        $data[0]['shadowBaseLanguage'] = null;

        $this->assertEquals($data[0], $response);
    }

    public function testCopyNonExistingSource()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        $client->request(
            'POST',
            '/api/nodes/123-123?webspace=sulu_io&language=en&action=copy&destination=' . $data[1]['id']
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testCopyNonExistingDestination()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = $this->buildTree();

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=copy&destination=123-123'
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testOrder()
    {
        $data = array(
            array(
                'title' => 'test1',
                'url' => '/test1'
            ),
            array(
                'title' => 'test2',
                'url' => '/test2'
            ),
            array(
                'title' => 'test3',
                'url' => '/test3'
            ),
            array(
                'title' => 'test4',
                'url' => '/test4'
            )
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[0]);
        $data[0] = json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[1]);
        $data[1] = json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[2]);
        $data[2] = json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[3]);
        $data[3] = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/nodes/' . $data[3]['id'] . '?webspace=sulu_io&language=en&action=order&destination=' . $data[0]['id']
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[3]['id'], $response['id']);
        $this->assertEquals('test4', $response['title']);
        $this->assertEquals('/test4', $response['path']);
        $this->assertEquals('/test4', $response['url']);

        $client->request(
            'POST',
            '/api/nodes/' . $data[2]['id'] . '?webspace=sulu_io&language=en&action=order&destination=' . $data[3]['id']
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[2]['id'], $response['id']);
        $this->assertEquals('test3', $response['title']);
        $this->assertEquals('/test3', $response['path']);
        $this->assertEquals('/test3', $response['url']);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals(4, sizeof($items));
        $this->assertEquals('test3', $items[0]['title']);
        $this->assertEquals('test4', $items[1]['title']);
        $this->assertEquals('test1', $items[2]['title']);
        $this->assertEquals('test2', $items[3]['title']);
    }

    public function testOrderNonExistingSource()
    {
        $data = array(
            array(
                'title' => 'test1',
                'url' => '/test1'
            )
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[0]);
        $data[0] = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/nodes/123-123-123?webspace=sulu_io&language=en&action=order&destination=' . $data[0]['id']
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testOrderNonExistingDestination()
    {
        $data = array(
            array(
                'title' => 'test1',
                'url' => '/test1'
            )
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data[0]);
        $data[0] = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=order&destination=123-123-123'
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testNavContexts()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $data = array(
            'title' => 'test1',
            'tags' => array(
                'tag1',
            ),
            'url' => '/test1',
            'article' => 'Test',
            'navContexts' => array('main', 'footer')
        );
        $client->request('POST', '/api/nodes?template=default&webspace=sulu_io&language=en', $data);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('test1', $data['title']);
        $this->assertEquals('/test1', $data['path']);
        $this->assertEquals(1, $data['nodeState']);
        $this->assertFalse($data['publishedState']);
        $this->assertEquals(array('main', 'footer'), $data['navContexts']);
        $this->assertFalse($data['hasSub']);
        $this->assertEquals(0, sizeof($data['_embedded']['nodes']));
        $this->assertArrayHasKey('_links', $data);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals(1, sizeof($items));

        $this->assertEquals(12, sizeof($items[0]));
        $this->assertArrayHasKey('id', $items[0]);
        $this->assertEquals('test1', $items[0]['title']);
        $this->assertEquals('/test1', $items[0]['path']);
        $this->assertEquals(1, $items[0]['nodeState']);
        $this->assertFalse($items[0]['publishedState']);
        $this->assertEquals(array('main', 'footer'), $items[0]['navContexts']);
        $this->assertFalse($items[0]['hasSub']);
        $this->assertEquals(0, sizeof($items[0]['_embedded']['nodes']));
        $this->assertArrayHasKey('_links', $items[0]);
    }

}
