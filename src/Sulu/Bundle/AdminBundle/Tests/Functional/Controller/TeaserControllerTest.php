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

class TeaserControllerTest extends SuluTestCase
{
    public function testCgetActionEmpty(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/api/teasers?locale=en');
        $response = \json_decode($client->getResponse()->getContent(), true);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $data = $response['_embedded']['teasers'];
        $this->assertEmpty($data);
    }
}
