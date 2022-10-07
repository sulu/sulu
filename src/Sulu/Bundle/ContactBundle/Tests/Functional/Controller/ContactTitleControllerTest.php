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
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ContactTitleControllerTest extends SuluTestCase
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
        $contactTitle1 = $this->createContactTitle('BSc');
        $contactTitle2 = $this->createContactTitle('MSc');

        $this->em->flush();

        $this->client->jsonRequest('GET', '/api/contact-titles');

        $response = \json_decode($this->client->getResponse()->getContent());
        $contactTitles = $response->_embedded->contact_titles;

        $this->assertCount(2, $contactTitles);
        $this->assertEquals($contactTitle1->getId(), $contactTitles[0]->id);
        $this->assertEquals('BSc', $contactTitles[0]->title);
        $this->assertEquals($contactTitle2->getId(), $contactTitles[1]->id);
        $this->assertEquals('MSc', $contactTitles[1]->title);
    }

    public function testCdeleteAction(): void
    {
        $contactTitle1 = $this->createContactTitle('BSc');
        $contactTitle2 = $this->createContactTitle('MSc');
        $contactTitle3 = $this->createContactTitle('PhD');

        $this->em->flush();

        $this->client->jsonRequest(
            'DELETE',
            '/api/contact-titles?ids=' . $contactTitle1->getId() . ',' . $contactTitle3->getId()
        );

        $this->client->jsonRequest('GET', '/api/contact-titles');

        $response = \json_decode($this->client->getResponse()->getContent());
        $contactTitles = $response->_embedded->contact_titles;

        $this->assertCount(1, $contactTitles);
        $this->assertEquals($contactTitle2->getId(), $contactTitles[0]->id);
        $this->assertEquals('MSc', $contactTitles[0]->title);
    }

    public function testCpatchAction(): void
    {
        $contactTitle1 = $this->createContactTitle('BSc');
        $contactTitle2 = $this->createContactTitle('MSc');

        $this->em->flush();

        $this->client->jsonRequest('PATCH', '/api/contact-titles', [
            ['id' => $contactTitle1->getId(), 'title' => 'BA'],
            ['title' => 'MA'],
        ]);

        $this->client->jsonRequest('GET', '/api/contact-titles');

        $response = \json_decode($this->client->getResponse()->getContent());
        $contactTitles = $response->_embedded->contact_titles;

        $this->assertCount(3, $contactTitles);
        $this->assertEquals('BA', $contactTitles[0]->title);
        $this->assertEquals('MA', $contactTitles[1]->title);
        $this->assertEquals('MSc', $contactTitles[2]->title);
    }

    private function createContactTitle(string $title)
    {
        $contactTitle = new ContactTitle();
        $contactTitle->setTitle($title);

        $this->em->persist($contactTitle);

        return $contactTitle;
    }
}
