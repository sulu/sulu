<?php

namespace Sulu\Bundle\ContactBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TemplateControllerTest extends WebTestCase
{
    public function testDetailsform()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/detailsForm');
    }

}
