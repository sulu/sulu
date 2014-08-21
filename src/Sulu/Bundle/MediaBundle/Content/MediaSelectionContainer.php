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
use Sulu\Bundle\MediaBundle\Api\Media;
use JMS\Serializer\Annotation\Exclude;

/**
 * Container for Image selection, holds config for image selection and lazy loads images matches the ids
 * @package Sulu\Bundle\MediaBundle\Content
 */
class MediaSelectionContainer implements \Serializable
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
     * @var string
     */
    private $displayOption;

    /**
     * @Exclude
     * @var string
     */
    private $locale;

    /**
     * @Exclude
     * @var Media[]
     */
    private $data;

    /**
     * @Exclude
     * @var string
     */
    private $types;

    /**
     * @Exclude
     * @var MediaManagerInterface
     */
    private $mediaManager;

    function __construct($config, $displayOption, $ids, $locale, $types, $mediaManager)
    {
        $this->config = $config;
        $this->displayOption = $displayOption;
        $this->ids = $ids;
        $this->locale = $locale;
        $this->types = $types;
        $this->mediaManager = $mediaManager;
    }

    /**
     * returns data of container
     * @return Media[]
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->data = $this->loadData($this->locale);
        }

        return $this->data;
    }

    /**
     * @param string $locale
     * @return Media[]
     */
    private function loadData($locale)
    {
        if (!empty($this->ids)) {
            return $this->mediaManager->get($locale, array('ids' => $this->ids));
        } else {
            return array();
        }
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
    public function getDisplayOption()
    {
        return $this->displayOption;
    }

    /**
     * @return string
     */
    public function getTypes()
    {
        return $this->types;
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
            case 'displayOption':
                return $this->getDisplayOption();
            case 'types':
                return $this->getTypes();
        }
        return null;
    }

    public function __isset($name)
    {
        return ($name == 'data' || $name == 'config' || $name == 'ids' || $name == 'displayOption' || $name == 'types');
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $result = array();
        foreach ($this->getData() as $data) {
            if ($data instanceof Media) {
                $result[] = $data->toArray();
            } else {
                $result[] = $data;
            }
        }

        return serialize(
            array(
                'data' => $result,
                'config' => $this->getConfig(),
                'ids' => $this->getIds(),
                'types' => $this->getTypes(),
                'displayOption' => $this->getDisplayOption()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $values = unserialize($serialized);
        $this->data = $values['data'];
        $this->config = $values['config'];
        $this->ids = $values['ids'];
        $this->types = $values['types'];
        $this->displayOption = $values['displayOption'];
    }
}
