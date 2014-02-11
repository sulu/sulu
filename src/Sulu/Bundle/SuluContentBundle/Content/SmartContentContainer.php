<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content;

use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Component\Content\StructureInterface;
use JMS\Serializer\Annotation\Exclude;

/**
 * Container for SmartContent, holds the config for a smart content, and lazy loads the structures meeting its criteria
 * @package Sulu\Bundle\ContentBundle\Content
 */
class SmartContentContainer
{
    /**
     * The node repository, which is needed for lazy loading the smart content data
     * @var NodeRepositoryInterface
     * @Exclude
     */
    private $nodeRepository;

    /**
     * Contains all the configuration for the smart content
     * @var array
     */
    private $config = array();

    /**
     * Stores all the structure meeting the filter criteria in the config.
     * Will be lazy loaded when accessed.
     * @var StructureInterface[]
     */
    private $data = null;

    public function __construct(NodeRepositoryInterface $nodeRepository) {
        $this->nodeRepository = $nodeRepository;
    }

    /**
     * Sets the config for this container
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the config for this container
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Lazy loads the data based on the filter criteria from the config
     * @return StructureInterface[]
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->nodeRepository->getSmartContentNodes($this->getConfig());
        }

        return $this->data;
    }
}
