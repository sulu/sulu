<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SecurityControllerTest extends SuluTestCase
{
    public function testLoginAction()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/admin/login');

        $this->assertGreaterThan(0, $crawler->filter('#main.login')->count());
    }

    public function testResetAction()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/admin/reset/test-token');

        $this->assertGreaterThan(0, $crawler->filter('#main.login')->count());
        $this->assertGreaterThan(0, $crawler->filter('div[data-aura-reset-token="test-token"]')->count());
    }
}
