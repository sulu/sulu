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

class ListTagActionTest extends SuluTestCase
{
    use SetupTrait;

    public function testListTag(): void
    {
        $this->createTag('first_tag');
        $this->createTag('second_tag');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/tags?flat=true'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals('first_tag', $response->_embedded->tags[0]->name);
        $this->assertEquals('second_tag', $response->_embedded->tags[1]->name);
    }

    public function createTag(string $name): void
    {
        $tag = $this->tagRepository->createNew();
        $tag->setName($name);
        $this->em->persist($tag);
    }
}
