<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TagCreateActionTest extends SuluTestCase
{
    use SetupTrait;

    public function testCreateTag(): void
    {
        $this->client->jsonRequest('POST', '/api/tags', ['name' => 'tag3']);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('tag3', $response->name);

        $this->client->jsonRequest('GET', '/api/tags/' . $response->id);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('tag3', $response['name']);
        $this->assertNotContains('creator', \array_keys($response));
        $this->assertNotContains('changer', \array_keys($response));
    }
}
