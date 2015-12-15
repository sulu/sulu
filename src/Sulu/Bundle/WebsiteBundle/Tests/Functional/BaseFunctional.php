<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManagerInterface;
use Sulu\Bundle\WebsiteBundle\Entity\Analytic;
use Sulu\Bundle\WebsiteBundle\Entity\Domain;
use Sulu\Bundle\WebsiteBundle\Entity\DomainRepository;

class BaseFunctional extends SuluTestCase
{
    /**
     * @var AnalyticsManagerInterface
     */
    protected $analyticsManager;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var DomainRepository
     */
    protected $domainRepository;

    public function setUp()
    {
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->analyticsManager = $this->getContainer()->get('sulu_website.analytics.manager');
        $this->domainRepository = $this->getContainer()->get('sulu_website.domains.repository');
    }

    /**
     * Create new analytic.
     *
     * @param string $webspaceKey
     * @param array $data
     *
     * @return Analytic
     */
    protected function create($webspaceKey, array $data)
    {
        $entity = $this->setData(new Analytic(), $webspaceKey, $data);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * Set data to given key.
     *
     * @param Analytic $analytic
     * @param string $webspaceKey
     * @param array $data
     *
     * @return Analytic
     */
    protected function setData(Analytic $analytic, $webspaceKey, array $data)
    {
        $analytic->setTitle($this->getValue($data, 'title'));
        $analytic->setType($this->getValue($data, 'type'));
        $analytic->setContent($this->getValue($data, 'content', ''));
        $analytic->setAllDomains($this->getValue($data, 'allDomains', false));
        $analytic->setWebspaceKey($webspaceKey);

        $analytic->clearDomains();

        foreach ($this->getValue($data, 'domains', []) as $domain) {
            $domainEntity = $this->findOrCreateNewDomain($domain);
            $analytic->addDomain($domainEntity);
        }

        return $analytic;
    }

    /**
     * Returns domain.
     * If the domain does not exists this function creates a new one.
     *
     * @param array $domain
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
     * @param array $data
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getValue(array $data, $name, $default = null)
    {
        if (!array_key_exists($name, $data)) {
            return $default;
        }

        return $data[$name];
    }
}
