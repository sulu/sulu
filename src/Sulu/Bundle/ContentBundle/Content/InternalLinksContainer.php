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

use PHPCR\ItemNotFoundException;
use Psr\Log\LoggerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
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
     * The content mapper, which is needed for lazy loading
     * @Exclude
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @Exclude
     * @var LoggerInterface
     */
    private $logger;

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
        ContentMapperInterface $contentMapper,
        LoggerInterface $logger,
        $webspaceKey,
        $languageCode
    ) {
        $this->ids = $ids;
        $this->contentMapper = $contentMapper;
        $this->logger = $logger;
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
        $result = array();
        if ($this->ids !== null) {
            foreach ($this->ids as $id) {
                try {
                    if (!empty($id)) {
                        $result[] = $this->contentMapper->load($id, $this->webspaceKey, $this->languageCode);
                    }
                } catch (ItemNotFoundException $ex) {
                    $this->logger->warning(
                        sprintf("%s in internal links not found. Exception: %s", $id, $ex->getMessage())
                    );
                }
            }
        }

        return $result;
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
