<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Trash;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\Admin\WebsiteAdmin;
use Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManager;
use Sulu\Bundle\WebsiteBundle\Domain\Event\AnalyticsRestoredEvent;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\Entity\Domain;
use Sulu\Bundle\WebsiteBundle\Entity\DomainRepository;
use Webmozart\Assert\Assert;

class AnalyticsTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface, RestoreConfigurationProviderInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var AnalyticsRepositoryInterface
     */
    private $analyticsRepository;

    /**
     * @var DomainRepository
     */
    private $domainRepository;

    /**
     * @var DoctrineRestoreHelperInterface
     */
    private $doctrineRestoreHelper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        AnalyticsRepositoryInterface $analyticsRepository,
        DomainRepository $domainRepository,
        DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        EntityManagerInterface $entityManager,
        DomainEventCollectorInterface $domainEventCollector,
        string $environment
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->analyticsRepository = $analyticsRepository;
        $this->domainRepository = $domainRepository;
        $this->doctrineRestoreHelper = $doctrineRestoreHelper;
        $this->entityManager = $entityManager;
        $this->domainEventCollector = $domainEventCollector;
        $this->environment = $environment;
    }

    public static function getResourceKey(): string
    {
        return AnalyticsInterface::RESOURCE_KEY;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(null, WebsiteAdmin::ANALYTICS_LIST_VIEW, ['webspaceKey' => 'webspace']);
    }

    /**
     * @param AnalyticsInterface $analytics
     */
    public function store(object $analytics): TrashItemInterface
    {
        Assert::isInstanceOf($analytics, AnalyticsInterface::class);

        $domains = $analytics->getDomains() ?? [];

        if ($domains instanceof Collection) {
            $domains = $domains->toArray();
        }

        return $this->trashItemRepository->create(
            AnalyticsInterface::RESOURCE_KEY,
            (string) $analytics->getId(),
            [
                'title' => $analytics->getTitle(),
                'type' => $analytics->getType(),
                'webspaceKey' => $analytics->getWebspaceKey(),
                'content' => $analytics->getContent(),
                'allDomains' => $analytics->isAllDomains(),
                'domains' => $domains,
            ],
            $analytics->getTitle(),
            WebsiteAdmin::getAnalyticsSecurityContext($analytics->getWebspaceKey()),
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        $analytics = $this->analyticsRepository->createNew();
        $this->setData($analytics, $data);

        $this->domainEventCollector->collect(
            new AnalyticsRestoredEvent($analytics, $data)
        );

        try {
            $this->analyticsRepository->findById($id);
        } catch (NoResultException $e) {
            $this->doctrineRestoreHelper->persistAndFlushWithId($analytics, $id);

            return $analytics;
        }

        $this->entityManager->persist($analytics);
        $this->entityManager->flush();

        return $analytics;
    }

    /**
     * @see AnalyticsManager::setData()
     *
     * @param array<string, mixed> $data
     */
    private function setData(AnalyticsInterface $analytics, array $data): void
    {
        $analytics->setTitle($this->getValue($data, 'title'));
        $analytics->setType($this->getValue($data, 'type'));
        $analytics->setWebspaceKey($this->getValue($data, 'webspaceKey'));
        $analytics->setContent($this->getValue($data, 'content', ''));
        $analytics->setAllDomains($this->getValue($data, 'allDomains', false));

        foreach ($this->getValue($data, 'domains', []) as $domain) {
            $domainEntity = $this->findOrCreateNewDomain($domain);
            $analytics->addDomain($domainEntity);
        }
    }

    /**
     * @see AnalyticsManager::findOrCreateNewDomain()
     */
    private function findOrCreateNewDomain(string $domain): Domain
    {
        /** @var Domain|null $domainEntity */
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
     * @see AnalyticsManager::getValue()
     *
     * @param array<string, mixed> $data
     * @param mixed|null $default
     *
     * @return mixed
     */
    private function getValue(array $data, string $name, $default = null)
    {
        if (!\array_key_exists($name, $data)) {
            return $default;
        }

        return $data[$name];
    }
}
