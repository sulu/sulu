<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class LocalizationControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
    }

    public function testCgetAction(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/admin/api/localizations'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $data = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(4, $data['_embedded']['localizations']);
    }
}
