<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Component\Contact\Repository\AccountContactRepositoryInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;

/**
 * DataProvider for contact.
 */
class ContactDataProvider implements DataProviderInterface
{
    /**
     * @var ProviderConfigurationInterface
     */
    private $configuration;

    /**
     * @var AccountContactRepositoryInterface
     */
    private $repository;

    public function __construct(AccountContactRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if (!$this->configuration) {
            return $this->initConfiguration();
        }

        return $this->configuration;
    }

    /**
     * Initiate configuration.
     *
     * @return ProviderConfigurationInterface
     */
    private function initConfiguration()
    {
        $this->configuration = new ProviderConfiguration();
        $this->configuration->setTags(true);
        $this->configuration->setCategories(false);
        $this->configuration->setLimit(true);
        $this->configuration->setPresentAs(true);
        $this->configuration->setPaginated(true);
        $this->configuration->setSorting([new PropertyParameter('c.name', 'public.name')]);

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveFilters(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        return $this->decorate($this->repository->findBy($filters, $limit, $page, $pageSize));
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getHasNextPage()
    {
        // TODO getHasNextPage()
        return false;
    }

    /**
     * Decorates result with item class.
     *
     * @param array $data
     *
     * @return array
     */
    private function decorate(array $data)
    {
        return array_map(
            function ($item) {
                if ($item instanceof Contact) {
                    return new ContactDataItem($item);
                } elseif ($item instanceof Account) {
                    return new AccountDataItem($item);
                }

                return;
            },
            $data
        );
    }
}
