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

class TagGetActionTest extends SuluTestCase
{
    use SetupTrait;

    public function testGetTag(): void
    {
        $tag = $this->tagRepository->createNew();
        $tag->setName("suluTag");
        $this->em->persist($tag);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/tags/' . $tag->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals("suluTag", $response["name"]);
    }
}
