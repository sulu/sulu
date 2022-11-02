<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PositionControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
    }

    public function testCgetAction(): void
    {
        $position1 = $this->createPosition('CEO');
        $position2 = $this->createPosition('CFO');

        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contact-positions');

        $response = \json_decode($this->client->getResponse()->getContent());
        $positions = $response->_embedded->contact_positions;

        $this->assertCount(2, $positions);
        $this->assertEquals($position1->getId(), $positions[0]->id);
        $this->assertEquals('CEO', $positions[0]->position);
        $this->assertEquals($position2->getId(), $positions[1]->id);
        $this->assertEquals('CFO', $positions[1]->position);
    }

    public function testCgetActionWithIds(): void
    {
        $position1 = $this->createPosition('CEO');
        $position2 = $this->createPosition('CFO');
        $position3 = $this->createPosition('COO');

        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contact-positions?ids=' . $position1->getId() . ',' . $position3->getId());

        $response = \json_decode($this->client->getResponse()->getContent());
        $positions = $response->_embedded->contact_positions;

        $this->assertCount(2, $positions);
        $this->assertEquals($position1->getId(), $positions[0]->id);
        $this->assertEquals('CEO', $positions[0]->position);
        $this->assertEquals($position3->getId(), $positions[1]->id);
        $this->assertEquals('COO', $positions[1]->position);
    }

    public function testCdeleteAction(): void
    {
        $position1 = $this->createPosition('CEO');
        $position2 = $this->createPosition('CFO');
        $position3 = $this->createPosition('CIO');

        $this->em->flush();

        $this->client->jsonRequest(
            'DELETE',
            '/api/contact-positions?ids=' . $position1->getId() . ',' . $position3->getId()
        );

        $this->client->jsonRequest('GET', '/api/contact-positions');

        $response = \json_decode($this->client->getResponse()->getContent());
        $positions = $response->_embedded->contact_positions;

        $this->assertCount(1, $positions);
        $this->assertEquals($position2->getId(), $positions[0]->id);
        $this->assertEquals('CFO', $positions[0]->position);
    }

    public function testCpatchAction(): void
    {
        $position1 = $this->createPosition('CEO');
        $position2 = $this->createPosition('CFO');

        $this->em->flush();

        $this->client->jsonRequest('PATCH', '/api/contact-positions', [
            ['id' => $position1->getId(), 'position' => 'CE'],
            ['position' => 'CIO'],
        ]);

        $this->client->jsonRequest('GET', '/api/contact-positions');

        $response = \json_decode($this->client->getResponse()->getContent());
        $positions = $response->_embedded->contact_positions;

        $this->assertCount(3, $positions);
        $this->assertEquals('CE', $positions[0]->position);
        $this->assertEquals('CFO', $positions[1]->position);
        $this->assertEquals('CIO', $positions[2]->position);
    }

    private function createPosition(string $position)
    {
        $contactPosition = new Position();
        $contactPosition->setPosition($position);

        $this->em->persist($contactPosition);

        return $contactPosition;
    }
}
