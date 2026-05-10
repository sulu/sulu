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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TagBundle\Entity\TagRepository;
use Sulu\Bundle\TestBundle\Testing\CreateClientTrait;
use Sulu\Bundle\TestBundle\Testing\PurgeDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait SetupTrait
{
    use PurgeDatabaseTrait;
    use CreateClientTrait;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $em;

    /**
     * @var TagRepositoryInterface
     */
    protected TagRepository $tagRepository;

    /**
     * @var KernelBrowser
     */
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();

        $this->tagRepository = $this->getContainer()->get('sulu.repository.tag');

        $this->initOrm();
    }

    protected function initOrm(): void
    {
        $this->purgeDatabase();
    }
}
