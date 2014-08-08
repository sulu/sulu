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
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\StructureInterface;
use JMS\Serializer\Annotation\Exclude;

/**
 * Container for SmartContent, holds the config for a smart content, and lazy loads the structures meeting its criteria
 * @package Sulu\Bundle\ContentBundle\Content
 */
class SmartContentContainer implements \Serializable
{
    /**
     * The node repository, which is needed for lazy loading the smart content data
     * @var NodeRepositoryInterface
     * @Exclude
     */
    private $nodeRepository;

    /**
     * Required for resolving the Tags to ids
     * @var TagManagerInterface
     * @Exclude
     */
    private $tagManager;

    /**
     * The key of the webspace for this smartcontent instance
     * @var string
     */
    private $webspaceKey;

    /**
     * The code of the language for this smartcontent instance
     * @var string
     */
    private $languageCode;

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

    /**
     * true environment is preview
     * @var bool
     */
    private $preview;

    /**
     * @param NodeRepositoryInterface $nodeRepository
     * @param TagManagerInterface $tagManager
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     * @param bool $preview
     */
    public function __construct(
        NodeRepositoryInterface $nodeRepository,
        TagManagerInterface $tagManager,
        $webspaceKey,
        $languageCode,
        $segmentKey,
        $preview = false
    )
    {
        $this->nodeRepository = $nodeRepository;
        $this->tagManager = $tagManager;
        $this->webspaceKey = $webspaceKey;
        $this->languageCode = $languageCode;
        $this->preview = $preview;
    }

    /**
     * Sets the config for this container
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        // TODO Remove when multi sorting is possible in javascript component
        if (isset($this->config['sortBy'])) {
            $this->config['sortBy'] = array($this->config['sortBy']);
        }
    }

    /**
     * Returns the config for this container
     * @return array
     */
    public function getConfig()
    {
        $config = $this->config;
        // TODO Remove when multi sorting is possible in javascript component
        if (isset($config['sortBy']) && is_array($config['sortBy']) && sizeof($config['sortBy']) > 0) {
            $config['sortBy'] = $config['sortBy'][0];
        }
        return $config;
    }

    /**
     * Lazy loads the data based on the filter criteria from the config
     * @return StructureInterface[]
     */
    public function getData()
    {
        if ($this->data === null) {
            // resolve tagNames to ids for loading data
            $config = $this->getConfig();
            if (!empty($config['tags'])) {
                $config['tags'] = $this->tagManager->resolveTagNames($config['tags']);
            }

            $this->data = $this->loadData($config);
        }

        return $this->data;
    }

    /**
     * lazy load data
     */
    private function loadData($config)
    {
        if (array_key_exists('dataSource', $config) && $config['dataSource'] !== '') {
            return $this->nodeRepository->getFilteredNodes(
                $config,
                $this->languageCode,
                $this->webspaceKey,
                $this->preview
            );
        } else {
            return array();
        }
    }

    /**
     * magic getter
     */
    public function __get($name)
    {
        switch ($name) {
            case 'data':
                return $this->getData();
            case 'config':
                return $this->getConfig();
        }
        return null;
    }

    /**
     * magic isset
     */
    public function __isset($name)
    {
        return ($name == 'data' || $name == 'config');
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return json_encode(
            array(
                'data'   => $this->getData(),
                'config' => $this->getConfig()
            )
        );
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $values = json_decode($serialized, true);
        $this->data = $values['data'];
        $this->config = $values['config'];
    }
}
