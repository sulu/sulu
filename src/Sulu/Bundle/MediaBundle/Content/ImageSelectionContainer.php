<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content;

use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\RestObject\Media;
use Sulu\Bundle\MediaBundle\Media\RestObject\RestObjectHelper;

/**
 * Container for Image selection, holds config for image selection and lazy loads images matches the ids
 * @package Sulu\Bundle\MediaBundle\Content
 */
class ImageSelectionContainer implements \Serializable
{
    /**
     * @var string[]
     */
    private $ids;

    /**
     * @var array
     */
    private $config;

    /**
     * @var mixed
     */
    private $direction;

    /**
     * @var string
     */
    private $localization;

    /**
     * @var Media[]
     */
    private $data;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    function __construct($config, $direction, $ids, $localization, $mediaManager)
    {
        $this->config = $config;
        $this->direction = $direction;
        $this->ids = $ids;
        $this->localization = $localization;
        $this->mediaManager = $mediaManager;
    }

    /**
     * returns data of container
     * @return Media[]
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    /**
     * @return Media[]
     */
    private function loadData()
    {
        $medias = $this->mediaManager->getMultiple($this->ids);

        return RestObjectHelper::convertMediasToRestObjects($medias, $this->localization);
    }

    /**
     * returns ids of container
     * @return string[]
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return mixed
     */
    public function getDirection()
    {
        return $this->direction;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'data':
                return $this->getData();
            case 'config':
                return $this->getConfig();
            case 'ids':
                return $this->getIds();
            case 'direction':
                return $this->getDirection();
        }
        return null;
    }

    public function __isset($name)
    {
        return ($name == 'data' || $name == 'config' || $name == 'ids' || $name == 'direction');
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return json_encode(
            array(
                'data' => $this->getData(),
                'config' => $this->getConfig(),
                'ids' => $this->getIds(),
                'direction' => $this->getDirection()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $values = json_decode($serialized, true);
        $this->data = $values['data'];
        $this->config = $values['config'];
        $this->ids = $values['ids'];
        $this->direction = $values['direction'];
    }
}
