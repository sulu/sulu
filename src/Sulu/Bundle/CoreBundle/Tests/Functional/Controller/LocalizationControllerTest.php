<?php

namespace Sulu\Bundle\CoreBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class LocalizationControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    public function setUp(): void
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
    }

    public function testCgetAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/admin/api/localizations'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(4, $data['_embedded']['localizations']);
    }
}
