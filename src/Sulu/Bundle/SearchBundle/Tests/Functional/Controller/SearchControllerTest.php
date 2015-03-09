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

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
        $this->searchManager = $this->client->getContainer()->get('massive_search.search_manager');

        $product = new Product();
        $product->id = 6;
        $product->title = 'Product X';
        $product->body = 'To be or not to be, that is the question';
        $product->url = '/foobar';
        $product->locale = 'fr';

        $this->searchManager->index($product);
    }

    public function provideSearch()
    {
        return array(
            array(
                'Product',
                array('product'),
                'fr',
                array(
                    array(
                        'document' => array(
                            'id' => 6,
                            'title' => 'Product X',
                            'description' => 'To be or not to be, that is the question',
                            'class' => 'Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product',
                            'url' => '/foobar',
                            'locale' => 'fr',
                            'index' => 'product',
                            'fields' => array(
                                'title' => array(
                                    'name' => 'title',
                                    'type' => 'string',
                                    'value' => 'Product X',
                                ),
                                'body' => array(
                                    'name' => 'body',
                                    'type' => 'string',
                                    'value' => 'To be or not to be, that is the question',
                                ),
                            ),
                        ),
                        'score' => -1,
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider provideSearch
     */
    public function testSearch($query, $indexes = null, $locale = null, $expectedResult)
    {
        $this->client->request('GET', '/api/search', array(
            'q' => $query,
            'indexes' => $indexes,
            'locale' => $locale,
        ));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);

        $this->assertEquals($expectedResult, $result);
    }
}

