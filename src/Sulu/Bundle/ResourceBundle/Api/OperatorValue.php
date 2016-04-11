<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Api;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ResourceBundle\Entity\OperatorValue as OperatorValueEntity;
use Sulu\Bundle\ResourceBundle\Entity\OperatorValueTranslation;
use Sulu\Component\Rest\ApiWrapper;

/**
 * The OperatorValue class which will be exported to the API.
 *
 * @ExclusionPolicy("all")
 */
class OperatorValue extends ApiWrapper
{
    /**
     * @param OperatorValueEntity $entity
     * @param string $locale
     */
    public function __construct(OperatorValueEntity $entity, $locale)
    {
        $this->entity = $entity;
        $this->locale = $locale;
    }

    /**
     * Returns the name of the operator value.
     *
     * @return string The name of the operator
     * @VirtualProperty
     * @SerializedName("name")
     */
    public function getName()
    {
        return $this->getTranslation()->getName();
    }

    /**
     * Sets the name of the operator value.
     *
     * @param string $name The name of the operator value
     */
    public function setName($name)
    {
        $this->getTranslation()->setName($name);
    }

    /**
     * Get translation by locale.
     *
     * @return OperatorValueTranslation
     */
    private function getTranslation()
    {
        $operatorTranslation = null;
        foreach ($this->entity->getTranslations() as $translation) {
            if ($translation->getLocale() == $this->locale) {
                $operatorTranslation = $translation;
            }
        }
        if (!$operatorTranslation) {
            $operatorTranslation = new OperatorValueTranslation();
            $operatorTranslation->setLocale($this->locale);
            $operatorTranslation->setOperatorValue($this->entity);
            $this->entity->addTranslation($operatorTranslation);
        }

        return $operatorTranslation;
    }

    /**
     * Set value.
     *
     * @param string $value
     *
     * @return OperatorValue
     */
    public function setValue($value)
    {
        $this->entity->setValue($value);
    }

    /**
     * Get value.
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("value")
     */
    public function getValue()
    {
        return $this->entity->getValue();
    }

    /**
     * Get id.
     *
     * @return int
     * @VirtualProperty
     * @SerializedName("id")
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Add translations.
     *
     * @param OperatorValueTranslation $translations
     */
    public function addTranslation(OperatorValueTranslation $translations)
    {
        $this->entity->addTranslation($translations);
    }

    /**
     * Remove translations.
     *
     * @param OperatorValueTranslation $translations
     */
    public function removeTranslation(OperatorValueTranslation $translations)
    {
        $this->entity->removeTranslation($translations);
    }

    /**
     * Get translations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->entity->getTranslations();
    }

    /**
     * Set operator.
     *
     * @param Operator $operator
     */
    public function setOperator(Operator $operator)
    {
        $this->entity->setOperator($operator->getEntity());
    }

    /**
     * Get operator.
     *
     * @return \Sulu\Bundle\ResourceBundle\Entity\Operator
     */
    public function getOperator()
    {
        return new Operator($this->entity->getOperator(), $this->locale);
    }
}
