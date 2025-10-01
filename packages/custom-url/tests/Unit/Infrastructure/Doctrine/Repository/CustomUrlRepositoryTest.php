<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Tests\Unit\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepository;

class CustomUrlRepositoryTest extends KernelTestCase
{
    use ProphecyTrait;

    private CustomUrlRepositoryInterface $customUrlRepository;

    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $this->entityManager = $this->getContainer()->get(EntityManagerInterface::class);

        $this->customUrlRepository = new CustomUrlRepository(
            $this->entityManager,
        );
    }

    public function testAddingCustomUrl(): void
    {
        $originalCustomUrl = new CustomUrl();
        $originalCustomUrl->setTitle('test-title-1');
        $originalCustomUrl->setWebspace('sulu_io');
        $originalCustomUrl->setBaseDomain('sulu-test.localhost/*/*');
        $originalCustomUrl->setDomainParts(['custom-path-1', 'custom-path-2']);
        $originalCustomUrl->setCanonical(true);
        $originalCustomUrl->setRedirect(false);
        $originalCustomUrl->setNoFollow(true);
        $originalCustomUrl->setNoIndex(false);
        $originalCustomUrl->setTargetDocument('23232323');
        $originalCustomUrl->setTargetLocale('de');
        $originalCustomUrl->setPublished(true);

        $this->customUrlRepository->add($originalCustomUrl);
        $this->entityManager->flush();

        $this->assertCount(1, $this->customUrlRepository->findBy());
    }
}
