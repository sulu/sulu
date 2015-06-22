<?php

namespace Sulu\Bundle\ContentBundle\Tests\Controller;

use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @group webtest
 */
class NodeResourcelocatorControllerTest extends SuluTestCase
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
        $this->session = $this->db('PHPCR')->getOm()->getPhpcrSession();
        $this->purgeDatabase();
        $this->initPhpcr();
        $this->data = $this->prepareRepositoryContent();
        $this->client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    private function prepareRepositoryContent()
    {
        $data = array(
            array(
                'title' => 'Produkte',
                'tags' => array(
                    'tag1',
                    'tag2',
                ),
                'url' => '/products',
                'article' => 'Test',
            ),
            array(
                'title' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2',
                ),
                'url' => '/news',
                'article' => 'Test',
            ),
            array(
                'title' => 'test',
                'tags' => array(
                    'tag1',
                    'tag2',
                ),
                'url' => '/news/test',
                'article' => 'Test',
            ),
            array(
                'title' => 'test-2',
                'tags' => array(
                    'tag1',
                    'tag2',
                ),
                'url' => '/news/test-1',
                'article' => 'Test',
            ),
            array(
                'title' => 'test',
                'tags' => array(
                    'tag1',
                    'tag2',
                ),
                'url' => '/news/test-1/test',
                'article' => 'Test',
            ),
        );

        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default', $data[0]);
        $data[0] = (array) json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default', $data[1]);
        $data[1] = (array) json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default&parent=' . $data[1]['id'], $data[2]);
        $data[2] = (array) json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default&parent=' . $data[1]['id'], $data[3]);
        $data[3] = (array) json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&template=default&parent=' . $data[3]['id'], $data[4]);
        $data[4] = (array) json_decode($client->getResponse()->getContent(), true);

        return $data;
    }

    public function testGenerate()
    {
        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?webspace=sulu_io&language=en&template=default',
            array('parts' => array('title' => 'test'))
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);

        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?parent=' . $this->data[0]['id'] . '&webspace=sulu_io&language=en&template=default',
            array('parts' => array('title' => 'test'))
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/products/test', $response->resourceLocator);

        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?parent=' . $this->data[1]['id'] . '&webspace=sulu_io&language=en&template=default',
            array('parts' => array('title' => 'test'))
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-2', $response->resourceLocator);

        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?parent=' . $this->data[3]['id'] . '&webspace=sulu_io&language=en&template=default',
            array('parts' => array('title' => 'test'))
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-1/test-1', $response->resourceLocator);
    }

    public function testGetAction()
    {
        // prepare history nodes
        $newsData = $this->data[1];
        $newsData['url'] = '/test';
        $this->client->request(
            'PUT',
            '/api/nodes/' . $newsData['id'] . '?webspace=sulu_io&language=en&template=default',
            $newsData
        );
        $newsData = (array) json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request(
            'GET',
            '/api/nodes/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&language=en'
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $result = (array) json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, sizeof($result['_embedded']['resourcelocators']));
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('/news', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
    }

    public function testDelete()
    {
        // prepare history nodes
        $newsData = $this->data[1];
        $newsData['url'] = '/test';
        $this->client->request(
            'PUT',
            '/api/nodes/' . $newsData['id'] . '?webspace=sulu_io&language=en&template=default',
            $newsData
        );
        $newsData = (array) json_decode($this->client->getResponse()->getContent());

        $this->client->request(
            'GET',
            '/api/nodes/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&language=en'
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $history = (array) json_decode($this->client->getResponse()->getContent(), true);

        $url = $history['_embedded']['resourcelocators'][0]['_links']['delete'];

        $url = substr($url, 6);
        $this->client->request('DELETE', $url);
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'GET',
            '/api/nodes/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&language=en'
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $result = (array) json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(0, sizeof($result['_embedded']['resourcelocators']));
        $this->assertEquals(0, $result['total']);
    }

    public function testRestore()
    {
        // prepare history nodes
        $newsData = $this->data[1];
        $newsData['url'] = '/test';
        $this->client->request(
            'PUT',
            '/api/nodes/' . $newsData['id'] . '?webspace=sulu_io&language=en&template=default',
            $newsData
        );
        $newsData = (array) json_decode($this->client->getResponse()->getContent());

        $this->client->request(
            'GET',
            '/api/nodes/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&language=en'
        );
        $node = (array) json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $history = (array) json_decode($this->client->getResponse()->getContent(), true);

        $url = $history['_embedded']['resourcelocators'][0]['_links']['restore'];
        $url = substr($url, 6);
        $this->client->request('PUT', $url);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'GET',
            '/api/nodes/' . $newsData['id'] . '?webspace=sulu_io&language=en'
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $node = (array) json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('/news', $node['url']);
    }
}
