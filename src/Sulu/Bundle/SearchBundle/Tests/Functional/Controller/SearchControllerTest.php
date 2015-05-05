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
                    'limit' => 10,
                    'pages' => 1,
                    '_embedded' => array(
                        'result' => array(
                        ),
                    ),
                    'totals' => array(
                        'page' => 0,
                        'account' => 0,
                        'contact' => 0,
                        'media' => 0,
                        'test_products' => 0,
                    ),
                    'total' => 0,
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
                    'limit' => 10,
                    'pages' => 1,
                    '_embedded' => array(
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
                                    'properties' => array(
                                        'title' => 'Product Xeon',
                                        'body' => 'To be or not to be, that is the question',
                                    ),
                                ),
                                'score' => -1,
                            ),
                        ),
                    ),
                    'totals' => array(
                        'test_products' => 1,
                        'account' => 0,
                        'contact' => 0,
                        'media' => 0,
                        'page' => 0,
                    ),
                    'total' => 1,
                ),
            ),
            array(
                array(
                    'q' => 'Xeon', 
                    'limit' => 1,
                    'page' => 2,
                ),
                array(
                    'page' => 2,
                    'limit' => 1,
                    'pages' => 2,
                    '_embedded' => array(
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
                                    'properties' => array(
                                        'title' => 'Car Xeon',
                                        'body' => 'To be or not to be, that is the question',
                                    ),
                                ),
                                'score' => -1,
                            ),
                        ),
                    ),
                    'totals' => array(
                        'test_products' => 2,
                        'account' => 0,
                        'contact' => 0,
                        'media' => 0,
                        'page' => 0,
                    ),
                    'total' => 2,
                ),
            ),
        );
    }

    /**
     * @dataProvider provideSearch
     */
    public function testSearch($params, $expectedResult)
    {
        foreach ($expectedResult['_embedded']['result'] as &$hitResult) {
            $hitResult['document']['creatorId'] = $this->user->getId();
            $hitResult['document']['changerId'] = $this->user->getId();
        }


        $this->client->request('GET', '/search/query', $params);

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        unset($result['_links']);

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

    public function setUp()
    {
        parent::setUp();
        $this->db('ORM')->purgeDatabase();
        $this->client = $this->createAuthenticatedClient();
        $this->searchManager = $this->client->getContainer()->get('massive_search.search_manager');
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createUser();
        $this->indexProducts();
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
}
