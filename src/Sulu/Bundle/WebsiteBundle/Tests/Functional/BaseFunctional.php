<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManagerInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\Entity\Domain;
use Sulu\Bundle\WebsiteBundle\Entity\DomainRepository;

class BaseFunctional extends SuluTestCase
{
    /**
     * @var AnalyticsManagerInterface
     */
    protected $analyticsManager;

    /**
     * @var AnalyticsRepositoryInterface
     */
    protected $analyticsRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var DomainRepository
     */
    protected $domainRepository;

    public function setUp(): void
    {
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->analyticsManager = $this->getContainer()->get('sulu_website.analytics.manager');

        /** @var AnalyticsRepositoryInterface $analyticsRepository */
        $analyticsRepository = $this->getContainer()->get('sulu_website_test.analytics.repository');
        $this->analyticsRepository = $analyticsRepository;

        /** @var DomainRepository $domainRepository */
        $domainRepository = $this->getContainer()->get('sulu_website_test.domains.repository');
        $this->domainRepository = $domainRepository;
    }

    protected function create(string $webspaceKey, array $data): AnalyticsInterface
    {
        $entity = $this->setData($this->analyticsRepository->createNew(), $webspaceKey, $data);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    protected function setData(AnalyticsInterface $analytics, string $webspaceKey, array $data): AnalyticsInterface
    {
        $analytics->setTitle($this->getValue($data, 'title'));
        $analytics->setType($this->getValue($data, 'type'));
        $analytics->setContent($this->getValue($data, 'content', ''));
        $analytics->setAllDomains($this->getValue($data, 'allDomains', false));
        $analytics->setWebspaceKey($webspaceKey);

        $analytics->clearDomains();

        foreach ($this->getValue($data, 'domains', []) as $domain) {
            $domainEntity = $this->findOrCreateNewDomain($domain);
            $analytics->addDomain($domainEntity);
        }

        return $analytics;
    }

    /**
     * Returns domain.
     * If the domain does not exists this function creates a new one.
     *
     * @return Domain
     */
    protected function findOrCreateNewDomain(array $domain)
    {
        $domainEntity = $this->domainRepository->findByUrlAndEnvironment($domain['url'], $domain['environment']);

        if (null !== $domainEntity) {
            return $domainEntity;
        }

        $domainEntity = new Domain();
        $domainEntity->setUrl($domain['url']);
        $domainEntity->setEnvironment($domain['environment']);

        $this->entityManager->persist($domainEntity);

        return $domainEntity;
    }

    /**
     * Returns property of data with given name.
     * If this property does not exists this function returns given default.
     *
     * @param string $name
     */
    protected function getValue(array $data, $name, $default = null)
    {
        if (!\array_key_exists($name, $data)) {
            return $default;
        }

        return $data[$name];
    }

    /**
     * @return EntityRepository<ActivityInterface>
     */
    protected function getActivityRepository(): EntityRepository
    {
        /** @var EntityRepository<ActivityInterface> $repository */
        $repository = $this->getEntityManager()->getRepository(ActivityInterface::class);

        return $repository;
    }

    /**
     * @return EntityRepository<TrashItemInterface>
     */
    protected function getTrashItemRepository(): EntityRepository
    {
        /** @var EntityRepository<TrashItemInterface> $repository */
        $repository = $this->getEntityManager()->getRepository(TrashItemInterface::class);

        return $repository;
    }
}
