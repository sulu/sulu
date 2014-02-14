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

    public function __construct(NodeRepositoryInterface $nodeRepository, TagManagerInterface $tagManager)
    {
        $this->nodeRepository = $nodeRepository;
        $this->tagManager = $tagManager;
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
            // resolve tagNames to ids for loading data
            $config = $this->getConfig();
            $config['tags'] = $this->tagManager->resolveTagNames($config['tags']);

            // TODO use correct language and workspace
            $this->data = $this->nodeRepository->getSmartContentNodes($config, 'en', 'sulu_io');
        }

        return $this->data;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'data':
                return $this->getData();
            case 'config':
                return $this->getConfig();
        }
    }

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
                'data' => $this->getData(),
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
