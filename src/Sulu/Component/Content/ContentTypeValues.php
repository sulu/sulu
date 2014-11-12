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

use Sulu\Component\Content\Exception\ContentTypeValuesServiceNotLoadedException;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class ContentTypeValues
 * @package Sulu\Component\Content
 */
class ContentTypeValues implements ContentTypeValuesInterface
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
        $type = self::TYPE_STATIC,
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
    public function loadValues(PropertyValueProviderManager $manager = null)
    {
        $propertyValues = array();

        if ($this->getType() == self::TYPE_SERVICE) {
            if (!$manager) {
                throw new ContentTypeValuesServiceNotLoadedException('App Container not given to load service "' . $this->serviceName . '"');
            }
            $propertyValues = $this->getServiceValues($manager);
        } else {
            foreach ($this->values as $value) {
                $propertyValues[] = $this->createPropertyValue($manager, $value);
            }
        }

        return $propertyValues;
    }

    /**
     * @param ContainerAware $container
     * @param $value
     * @return PropertyValue
     */
    private function createPropertyValue(PropertyValueProviderManager $manager = null, $value) {
        $propertyValue = new PropertyValue();
        foreach ($value as $attributeKey => $attributeValue) {
            if ($attributeKey == 'children') {
                // get children values
                foreach ($attributeValue as $values) {
                    if ($values['values']) {
                        // create PropertyValues
                        $child = new ContentTypeValues(
                            $values['values'],
                            isset($values['type']) ? $values['type'] : self::TYPE_STATIC,
                            isset($values['id']) ? $values['id'] : null
                        );
                        // get Values
                        $childValues = $child->getValues($manager);
                        foreach ($childValues as $childValue) {
                            $propertyValue->addChildren($childValue);
                        }
                    }
                }
            } else {
                $propertyValue->setAttribute($attributeKey, $attributeValue);
            }
        }

        return $propertyValue;
    }

    /**
     * @param ContainerAware $container
     * @return array
     * @throws ContentTypeValuesServiceNotLoadedException
     */
    private function getServiceValues(PropertyValueProviderManager $manager)
    {
        $service = $manager->get($this->serviceName);
        if (!($service instanceof ContentTypeValuesServiceInterface)) {
            throw new ContentTypeValuesServiceNotLoadedException('Service not loaded correctly "' . $this->serviceName . '"');
        }
        $propertyValues = array();
        foreach ($service->getValues($this->values) as $key => $value) {
            $propertyValues[] = $this->createPropertyValue($container, $value);
        }
        return $propertyValues;
    }

} 
