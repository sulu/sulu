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

class TagPutActionTest extends SuluTestCase
{
    use SetupTrait;

    public function testUpdateTag(): void
    {
        $tag = $this->tagRepository->createNew();
        $tag->setName("nice_tag");
        $this->em->persist($tag);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/tags/' . $tag->getId(),
            ["name" => "amazing_tag"]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('amazing_tag', $response->name);

        $this->client->jsonRequest(
            'GET',
            '/api/tags/' . $tag->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('amazing_tag', $response['name']);
    }
}
