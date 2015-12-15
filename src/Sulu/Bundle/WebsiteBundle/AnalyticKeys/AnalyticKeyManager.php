<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\AnalyticKeys;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticKey;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticKeyDomain;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticKeyRepository;

/**
 * Manages analytic keys.
 */
class AnalyticKeyManager implements AnalyticKeyManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AnalyticKeyRepository
     */
    private $repository;

    /**
     * @var EntityRepository
     */
    private $domainRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnalyticKeyRepository $repository,
        EntityRepository $domainRepository
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->domainRepository = $domainRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($webspaceKey)
    {
        return $this->repository->findByWebspaceKey($webspaceKey);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->repository->findById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create($webspaceKey, $data)
    {
        $entity = new AnalyticKey();
        $this->setData($entity, $webspaceKey, $data);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, $data)
    {
        $entity = $this->find($id);
        $this->setData($entity, $entity->getWebspaceKey(), $data);

        $this->entityManager->flush();

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->entityManager->remove($this->entityManager->getReference(AnalyticKey::class, $id));
    }

    /**
     * Set data to given key.
     *
     * @param AnalyticKey $analyticKey
     * @param string $webspaceKey
     * @param array $data
     */
    private function setData(AnalyticKey $analyticKey, $webspaceKey, $data)
    {
        $analyticKey->setTitle($this->getValue($data, 'title'));
        $analyticKey->setType($this->getValue($data, 'type'));
        $analyticKey->setContent($this->getValue($data, 'content', ''));
        $analyticKey->setAllDomains($this->getValue($data, 'allDomains', false));
        $analyticKey->setWebspaceKey($webspaceKey);

        $analyticKey->clearDomains();

        foreach ($this->getValue($data, 'domains', []) as $domain) {
            $domainEntity = $this->findOrCreateNewDomain($domain);
            $analyticKey->addDomain($domainEntity);
        }
    }

    /**
     * Returns domain.
     * If the domain does not exists this function creates a new one.
     *
     * @param array $domain
     *
     * @return AnalyticKeyDomain
     */
    private function findOrCreateNewDomain(array $domain)
    {
        $domainEntity = $this->domainRepository->find($domain['url']);

        if (null !== $domainEntity) {
            return $domainEntity;
        }

        $domainEntity = new AnalyticKeyDomain();
        $domainEntity->setUrl($domain['url']);

        $this->entityManager->persist($domainEntity);

        return $domainEntity;
    }

    /**
     * Returns property of data with given name.
     * If this property does not exists this function returns given default.
     *
     * @param string $data
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    private function getValue($data, $name, $default = null)
    {
        if (!array_key_exists($name, $data)) {
            return $default;
        }

        return $data[$name];
    }
}
