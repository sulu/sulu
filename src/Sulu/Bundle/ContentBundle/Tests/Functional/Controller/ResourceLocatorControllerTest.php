<?php

namespace Sulu\Bundle\ContentBundle\Tests\Controller;

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
        $this->prepareSession();

        NodeHelper::purgeWorkspace($this->session);
        $this->session->save();

        $this->prepareRepository();
        $this->session->save();

        $cmf = $this->session->getRootNode()->addNode('cmf');
        $webspace = $cmf->addNode('default');
        $webspace->addNode('routes');
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

    private function prepareSession()
    {
        $factoryclass = '\Jackalope\RepositoryFactoryJackrabbit';
        $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
        $factory = new $factoryclass();
        $repository = $factory->getRepository($parameters);
        $credentials = new \PHPCR\SimpleCredentials('admin', 'admin');
        $this->session = $repository->login($credentials, 'test');
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

        /** @var ContentMapperInterface $mapper */
        $mapper = self::$kernel->getContainer()->get('sulu.content.mapper');

        $data[0] = $mapper->save($data[0], 'overview', 'default', 'en', 1)->toArray();
        $data[1] = $mapper->save($data[1], 'overview', 'default', 'en', 1)->toArray();
        $data[2] = $mapper->save($data[2], 'overview', 'default', 'en', 1, true, null, $data[1]['id'])->toArray();
        $data[3] = $mapper->save($data[3], 'overview', 'default', 'en', 1, true, null, $data[1]['id'])->toArray();
        $data[4] = $mapper->save($data[4], 'overview', 'default', 'en', 1, true, null, $data[3]['id'])->toArray();

        return $data;
    }

    public function testGet()
    {
        $this->client->request('GET', '/content/resourcelocator.json?title=test&webspace=default');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);

        $this->client->request('GET', '/content/resourcelocator.json?parent=' . $this->data[0]['id'] . '&title=test&webspace=default');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/products/test', $response->resourceLocator);

        $this->client->request('GET', '/content/resourcelocator.json?parent=' . $this->data[1]['id'] . '&title=test&webspace=default');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-2', $response->resourceLocator);

        $this->client->request('GET', '/content/resourcelocator.json?parent=' . $this->data[3]['id'] . '&title=test&webspace=default');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-1/test-1', $response->resourceLocator);
    }
}
