<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Analytics;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\EventLogBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\WebsiteBundle\Domain\Event\AnalyticsCreatedEvent;
use Sulu\Bundle\WebsiteBundle\Domain\Event\AnalyticsModifiedEvent;
use Sulu\Bundle\WebsiteBundle\Domain\Event\AnalyticsRemovedEvent;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\Entity\Domain;
use Sulu\Bundle\WebsiteBundle\Entity\DomainRepository;

/**
 * Manages analytics.
 */
class AnalyticsManager implements AnalyticsManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AnalyticsRepositoryInterface
     */
    private $analyticsRepository;

    /**
     * @var DomainRepository
     */
    private $domainRepository;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnalyticsRepositoryInterface $analyticsRepository,
        DomainRepository $domainRepository,
        string $environment,
        DomainEventCollectorInterface $domainEventCollector
    ) {
        $this->entityManager = $entityManager;
        $this->analyticsRepository = $analyticsRepository;
        $this->domainRepository = $domainRepository;
        $this->environment = $environment;
        $this->domainEventCollector = $domainEventCollector;
    }

    public function findAll($webspaceKey)
    {
        return $this->analyticsRepository->findByWebspaceKey($webspaceKey, $this->environment);
    }

    public function find($id)
    {
        return $this->analyticsRepository->findById($id);
    }

    public function create($webspaceKey, $data)
    {
        /** @var AnalyticsInterface $entity */
        $entity = $this->analyticsRepository->createNew();
        $this->setData($entity, $webspaceKey, $data);

        $this->entityManager->persist($entity);

        $this->domainEventCollector->collect(new AnalyticsCreatedEvent($entity, $data));

        return $entity;
    }

    public function update($id, $data)
    {
        $entity = $this->find($id);
        $this->setData($entity, $entity->getWebspaceKey(), $data);

        $this->domainEventCollector->collect(new AnalyticsModifiedEvent($entity, $data));

        return $entity;
    }

    public function remove($id)
    {
        $entity = $this->find($id);

        $webspaceKey = $entity->getWebspaceKey();
        $title = $entity->getTitle();

        $this->entityManager->remove($entity);

        $this->domainEventCollector->collect(new AnalyticsRemovedEvent($id, $webspaceKey, $title));
    }

    public function removeMultiple(array $ids)
    {
        foreach ($ids as $id) {
            $this->remove($id);
        }
    }

    /**
     * Set data to given key.
     *
     * @param string $webspaceKey
     * @param array $data
     */
    private function setData(AnalyticsInterface $analytics, $webspaceKey, $data)
    {
        $analytics->setTitle($this->getValue($data, 'title'));
        $analytics->setType($this->getValue($data, 'type'));
        $analytics->setContent($this->getValue($data, 'content', ''));
        $analytics->setAllDomains($this->getValue($data, 'allDomains', false));
        $analytics->setWebspaceKey($webspaceKey);

        if ($analytics->isAllDomains()) {
            $analytics->clearDomains();

            return;
        }

        $domains = [];
        $domainCollection = $analytics->getDomains();
        if ($domainCollection) {
            $domains = $domainCollection->toArray();
        }
        foreach ($this->getValue($data, 'domains', []) as $domain) {
            if (\in_array($domain, $domains)) {
                unset($domains[\array_search($domain, $domains)]);

                continue;
            }

            $domainEntity = $this->findOrCreateNewDomain($domain);
            $analytics->addDomain($domainEntity);
        }

        foreach ($domains as $domain) {
            $domainEntity = $this->findOrCreateNewDomain($domain);
            $analytics->removeDomain($domainEntity);
        }
    }

    private function findOrCreateNewDomain(string $domain): Domain
    {
        $domainEntity = $this->domainRepository->findByUrlAndEnvironment($domain, $this->environment);

        if (null !== $domainEntity) {
            return $domainEntity;
        }

        $domainEntity = new Domain();
        $domainEntity->setUrl($domain);
        $domainEntity->setEnvironment($this->environment);

        $this->entityManager->persist($domainEntity);

        return $domainEntity;
    }

    /**
     * Returns property of data with given name.
     * If this property does not exists this function returns given default.
     *
     * @param string $data
     * @param string $name
     */
    private function getValue($data, $name, $default = null)
    {
        if (!\array_key_exists($name, $data)) {
            return $default;
        }

        return $data[$name];
    }
}
