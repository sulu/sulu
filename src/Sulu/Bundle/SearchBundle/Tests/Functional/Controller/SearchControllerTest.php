<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\BrowserKit\Client;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SearchBundle\Tests\Fixtures\DefaultStructureCache;
use Sulu\Component\Content\StructureInterface;

class SearchControllerTest extends SuluTestCase
{
    /**
     * @var SearchController
     */
    private $controller;

    /**
     * @var SearchManager
     */
    private $searchManager;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var User
     */
    private $user;

    public function setUp()
    {
        parent::setUp();
        $this->db('ORM')->purgeDatabase();
        $this->client = $this->createAuthenticatedClient();
        $this->searchManager = $this->client->getContainer()->get('massive_search.search_manager');
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createUser();
        $this->indexProducts();
        $this->indexStructures();
    }

    private function createUser()
    {
        $user = new User();
        $user->setUsername('dantleech');
        $user->setPassword('mypassword');
        $user->setLocale('en');
        $user->setSalt('12345');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->user = $user;
    }

    private function indexProducts()
    {
        $product = new Product();
        $product->id = 6;
        $product->title = 'Product Xeon';
        $product->body = 'To be or not to be, that is the question';
        $product->url = '/foobar';
        $product->locale = 'fr';
        $product->created = new \DateTime('2015-04-10T00:00:00+00:00');
        $product->changed = new \DateTime('2015-04-12T00:00:00+00:00');
        $product->changer = $this->user;
        $product->creator = $this->user;

        $this->searchManager->index($product);

        $product = new Product();
        $product->id = 7;
        $product->title = 'Car Xeon';
        $product->body = 'To be or not to be, that is the question';
        $product->url = '/foobar';
        $product->locale = 'fr';
        $product->created = new \DateTime('2015-04-10T00:00:00+00:00');
        $product->changed = new \DateTime('2015-04-12T00:00:00+00:00');
        $product->changer = $this->user;
        $product->creator = $this->user;

        $this->searchManager->index($product);
    }

    private function indexStructures()
    {
        $structure = new DefaultStructureCache();
        $structure->setUuid(1234);
        $structure->setWebspaceKey('sulu_io');
        $structure->setChanged(new \DateTime('2015-04-12T00:00:00+00:00'));
        $structure->setCreated(new \DateTime('2015-04-10T00:00:00+00:00'));
        $structure->setChanger($this->user->getId());
        $structure->setCreator($this->user->getId());

        $structure->getProperty('url')->setValue('/');
        $structure->getProperty('title')->setValue('Hello');
        $structure->setNodeState(StructureInterface::STATE_PUBLISHED);
        $structure->setLanguageCode('de');

        $this->searchManager->index($structure);
    }

    public function provideSearch()
    {
        return array(
            array(
                array(
                    'q' => 'Hello',
                    'locale' => 'de',
                ),
                array(
                    'page' => 1,
                    'page_size' => 50,
                    'page_count' => 1,
                    'result' => array(
                        array(
                            'id' => null,
                            'document' => array(
                                'id' => 1234,
                                'title' => 'Hello',
                                'description' => null,
                                'url' => '/',
                                'locale' => 'de',
                                'imageUrl' => null,
                                'category' => 'page',
                                'created' => '2015-04-10T00:00:00+00:00',
                                'changed' => '2015-04-12T00:00:00+00:00',
                                'creatorName' => 'dantleech',
                                'changerName' => 'dantleech',
                            ),
                            'score' => -1,
                        ),
                    ),
                ),
            ),
            array(
                array(
                    'q' => 'Product', 
                    'indexes' => array('Product'), 
                    'locale' => 'fr',
                ),
                array(
                    'page' => 1,
                    'page_size' => 50,
                    'page_count' => 1,
                    'result' => array(
                        array(
                            'id' => null,
                            'document' => array(
                                'id' => 6,
                                'title' => 'Product Xeon',
                                'description' => 'To be or not to be, that is the question',
                                'url' => '/foobar',
                                'locale' => 'fr',
                                'imageUrl' => null,
                                'category' => 'test_products',
                                'created' => '2015-04-10T00:00:00+00:00',
                                'changed' => '2015-04-12T00:00:00+00:00',
                                'creatorName' => 'dantleech',
                                'changerName' => 'dantleech',
                            ),
                            'score' => -1,
                        ),
                    ),
                ),
            ),
            array(
                array(
                    'q' => 'Xeon', 
                    'page_size' => 1,
                    'page' => 2,
                ),
                array(
                    'page' => 2,
                    'page_size' => 1,
                    'page_count' => 2,
                    'result' => array(
                        array(
                            'id' => null,
                            'document' => array(
                                'id' => 7,
                                'title' => 'Car Xeon',
                                'description' => 'To be or not to be, that is the question',
                                'url' => '/foobar',
                                'locale' => 'fr',
                                'imageUrl' => null,
                                'category' => 'test_products',
                                'created' => '2015-04-10T00:00:00+00:00',
                                'changed' => '2015-04-12T00:00:00+00:00',
                                'creatorName' => 'dantleech',
                                'changerName' => 'dantleech',
                            ),
                            'score' => -1,
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider provideSearch
     */
    public function testSearch($params, $expectedResult)
    {
        foreach ($expectedResult['result'] as &$hitResult) {
            $hitResult['document']['creatorId'] = $this->user->getId();
            $hitResult['document']['changerId'] = $this->user->getId();
        }

        $this->client->request('GET', '/search/query', $params);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetCategories()
    {
        $this->client->request('GET', '/search/categories');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);

        $this->assertContains('test_products', $result);
    }
}
