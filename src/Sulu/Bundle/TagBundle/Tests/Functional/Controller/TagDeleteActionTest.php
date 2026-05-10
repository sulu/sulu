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

class TagDeleteActionTest extends SuluTestCase
{
    use SetupTrait;

    public function testDeleteTag(): void
    {
        $tag = $this->tagRepository->createNew();
        $tag->setName("sulu_tag");
        $this->em->persist($tag);
        $this->em->flush();

        $tagId = $tag->getId();

        // $this->client->getContainer()->get('event_dispatcher')
        //         ->addListener('sulu.tag.delete', fn () => $eventListenerWasCalled = true);

        $this->client->jsonRequest(
            'DELETE',
            '/api/tags/' . $tag->getId()
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->em->clear();

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(null, $response);

        $this->client->jsonRequest(
            'GET',
            '/api/tags/' . $tagId
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }
}
