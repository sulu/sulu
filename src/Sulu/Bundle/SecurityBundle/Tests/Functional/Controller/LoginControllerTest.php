<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class LoginControllerTest extends SuluTestCase
{
    public function testFalseLoginRedirect(): void
    {
        $client = $this->createClient();
        $client->request('POST', '/admin/login', [
            '_username' => 'FalseUser',
            '_password' => 'FalsePassword',
        ]);
        $response = $client->getResponse();
        $this->assertHttpStatusCode(302, $response);
        $this->assertSame('/admin/', $response->getTargetUrl());
    }
}
