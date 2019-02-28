<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use Sulu\Component\SmartContent\Exception\DataProviderAliasAlreadyExistsException;
use Sulu\Component\SmartContent\Exception\DataProviderNotExistsException;

/**
 * Collects all DataProvider and provides them.
 */
class DataProviderPool implements DataProviderPoolInterface
{
    /**
     * @var bool
     */
    private $hasAudienceTargeting = false;

    /**
     * @var DataProviderInterface[]
     */
    private $providers = [];

    public function __construct(bool $hasAudienceTargeting)
    {
        $this->hasAudienceTargeting = $hasAudienceTargeting;
    }

    /**
     * {@inheritdoc}
     */
    public function add($alias, DataProviderInterface $provider)
    {
        if ($this->exists($alias)) {
            throw new DataProviderAliasAlreadyExistsException($alias);
        }

        if (!$this->hasAudienceTargeting) {
            $provider->getConfiguration()->setAudienceTargeting(false);
        }

        $this->providers[$alias] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($alias)
    {
        return array_key_exists($alias, $this->providers);
    }

    /**
     * {@inheritdoc}
     */
    public function get($alias)
    {
        if (!$this->exists($alias)) {
            throw new DataProviderNotExistsException($alias);
        }

        return $this->providers[$alias];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        return $this->providers;
    }
}
