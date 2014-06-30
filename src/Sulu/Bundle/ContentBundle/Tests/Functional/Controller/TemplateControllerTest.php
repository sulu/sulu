<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

class TemplateControllerTest extends DatabaseTestCase
{
    public $structureFactoryMock;
    public $container;

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testContentForm()
    {
        $client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
        $crawler = $client->request('GET', '/content/template/form/default.html');

        $this->assertTrue(200 === $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filter('form#content-form')->count());

        // foreach property one textfield
        $this->assertEquals(1, $crawler->filter('input#title')->count());
        $this->assertEquals(1, $crawler->filter('div#url')->count());
        $this->assertEquals(1, $crawler->filter('textarea#article')->count());
        // for tags 2
        $this->assertEquals(1, $crawler->filter('div#tags')->count());
    }

}
