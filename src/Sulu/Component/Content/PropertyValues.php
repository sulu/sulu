<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class PropertyValues
 * @package Sulu\Component\Content
 */
class PropertyValues implements PropertyValuesInterface
{
    /**
     * @var $values
     */
    private $values;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $serviceName;


    public function __construct(
        $values = array(),
        $type = 'static',
        $serviceName = null
    )
    {
        $this->values = $values;
        $this->type = $type;
        $this->serviceName = $serviceName;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->getType();
    }

    /**
     * {@inheritDoc}
     */
    public function getValues(ContainerAware $container = null)
    {
        if ($this->getType() == self::TYPE_SERVICE) {
            return $this->getServiceValues($container, $this->serviceName, $this->values);
        }

        return $this->values;
    }

    /**
     * @param ContainerAware $container
     * @param $serviceName
     * @param $values
     * @return array
     * @throws \Exception
     */
    private function getServiceValues(ContainerAware $container, $serviceName, $values = array())
    {
        if (!$container) {
            throw new \Exception('App Container not given to load service "' . $serviceName . '"');
        }
        $service = $container->get($this->serviceName);
        if (!($service instanceof PropertyValuesServiceInterface)) {
            throw new \Exception('App Container not given to load service "' . $serviceName . '"');
        }
        $params = array();
        /** @var PropertyValuesInterface $value */
        foreach ($values as $key => $value) {
            $params[$key] = $value->getValues($container);
        }
        return $service->getValues($params);
    }

} 
