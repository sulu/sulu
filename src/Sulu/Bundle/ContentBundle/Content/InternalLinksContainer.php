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

use Psr\Log\LoggerInterface;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Component\Content\StructureInterface;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Container for InternalLinks, holds the config for a internal links, and lazy loads the structures
 * @package Sulu\Bundle\ContentBundle\Content
 */
class InternalLinksContainer implements ArrayableInterface
{
    /**
     * The node repository, which is needed for lazy loading
     * @Exclude
     * @var NodeRepositoryInterface
     */
    private $repository;

    /**
     * The key of the webspace
     * @Exclude
     * @var string
     */
    private $webspaceKey;

    /**
     * The code of the language
     * @Exclude
     * @var string
     */
    private $languageCode;

    /**
     * @var string[]
     */
    private $ids;

    /**
     * @Exclude
     * @var StructureInterface[]
     */
    private $data;

    public function __construct(
        $ids,
        NodeRepositoryInterface $repository,
        $webspaceKey,
        $languageCode
    ) {
        $this->ids = $ids;
        $this->repository = $repository;
        $this->webspaceKey = $webspaceKey;
        $this->languageCode = $languageCode;
    }

    /**
     * Lazy loads the data based on the filter criteria from the config
     * @return StructureInterface[]
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    /**
     * lazy load data
     */
    private function loadData()
    {
        if ($this->ids !== null) {
            return $this->repository->getNodesByIds($this->ids, $this->webspaceKey, $this->languageCode)['_embedded']['nodes'];
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
        }
        return null;
    }

    /**
     * magic isset
     */
    public function __isset($name)
    {
        return ($name == 'data');
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        return array('ids' => $this->ids);
    }
}
