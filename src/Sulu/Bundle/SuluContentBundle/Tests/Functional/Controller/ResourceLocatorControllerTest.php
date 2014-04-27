<?php

namespace Sulu\Bundle\ContentBundle\Tests\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class ResourceLocatorControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $data;

    protected function setUp()
    {
        $this->setUpSchema();
        $this->prepareSession();

        NodeHelper::purgeWorkspace($this->session);
        $this->session->save();

        $this->session->save();

        $cmf = $this->session->getRootNode()->addNode('cmf');
        $webspace = $cmf->addNode('sulu_io');
        $nodes = $webspace->addNode('routes');
        $nodes->addNode('en');
        $webspace->addNode('contents');

        $this->session->save();

        $this->client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );

        $this->data = $this->prepareRepositoryContent();
    }

    private function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser')
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
        $this->session = $repository->login($credentials, 'test');
    }

    protected function tearDown()
    {
        if ($this->session != null) {
            NodeHelper::purgeWorkspace($this->session);
            $this->session->save();
        }
        parent::tearDown();
    }

    private function prepareRepositoryContent()
    {
        $data = array(
            array(
                'title' => 'Produkte',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/products',
                'article' => 'Test'
            ),
            array(
                'title' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'Test'
            ),
            array(
                'title' => 'test',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test',
                'article' => 'Test'
            ),
            array(
                'title' => 'test-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'Test'
            ),
            array(
                'title' => 'test',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1/test',
                'article' => 'Test'
            )
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default', $data[0]);
        $data[0] = (array) json_decode($client->getResponse()->getContent());
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default', $data[1]);
        $data[1] = (array) json_decode($client->getResponse()->getContent());
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default&parent='.$data[1]['id'], $data[2]);
        $data[2] = (array) json_decode($client->getResponse()->getContent());
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default&parent='.$data[1]['id'], $data[3]);
        $data[3] = (array) json_decode($client->getResponse()->getContent());
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default&parent='.$data[3]['id'], $data[4]);
        $data[4] = (array) json_decode($client->getResponse()->getContent());

        return $data;
    }

    public function testGet()
    {
        $this->client->request(
            'GET',
            '/content/resourcelocator.json?webspace=sulu_io&language=en&template=default',
            array('parts' => array('title' => 'test'))
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);

        $this->client->request(
            'GET',
            '/content/resourcelocator.json?parent=' . $this->data[0]['id'] . '&webspace=sulu_io&language=en&template=default',
            array('parts' => array('title' => 'test'))
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/products/test', $response->resourceLocator);

        $this->client->request(
            'GET',
            '/content/resourcelocator.json?parent=' . $this->data[1]['id'] . '&webspace=sulu_io&language=en&template=default',
            array('parts' => array('title' => 'test'))
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-2', $response->resourceLocator);

        $this->client->request(
            'GET',
            '/content/resourcelocator.json?parent=' . $this->data[3]['id'] . '&webspace=sulu_io&language=en&template=default',
            array('parts' => array('title' => 'test'))
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-1/test-1', $response->resourceLocator);
    }
}
