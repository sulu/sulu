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

use Hateoas\Configuration\Annotation\Relation;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ResourceBundle\Entity\Filter as FilterEntity;
use Sulu\Bundle\ResourceBundle\Entity\FilterTranslation;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * The Filter class which will be exported to the API.
 *
 * @Relation("self", href="expr('/admin/api/filters/' ~ object.getId())")
 * @ExclusionPolicy("all")
 */
class Filter extends ApiWrapper
{
    /**
     * @param FilterEntity $entity
     * @param string $locale
     */
    public function __construct(FilterEntity $entity, $locale)
    {
        $this->entity = $entity;
        $this->locale = $locale;
    }

    /**
     * Get id.
     *
     * @VirtualProperty
     * @SerializedName("id")
     *
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Returns the name of the filter.
     *
     * @return string The name of the filter
     * @VirtualProperty
     * @SerializedName("name")
     */
    public function getName()
    {
        return $this->getTranslation()->getName();
    }

    /**
     * Get conjunction.
     *
     * @VirtualProperty
     * @SerializedName("conjunction")
     *
     * @return bool
     */
    public function getConjunction()
    {
        return $this->entity->getConjunction();
    }

    /**
     * Get entity.
     *
     * @VirtualProperty
     * @SerializedName("context")
     *
     * @return string
     */
    public function getContext()
    {
        return $this->entity->getContext();
    }

    /**
     * Get conditionGroups.
     *
     * @VirtualProperty
     * @SerializedName("conditionGroups")
     *
     * @return null|ConditionGroup[]
     */
    public function getConditionGroups()
    {
        $groups = $this->entity->getConditionGroups();
        if ($groups) {
            $result = [];
            foreach ($groups as $group) {
                $result[] = new ConditionGroup($group, $this->locale);
            }

            return $result;
        }

        return;
    }

    /**
     * Set conjunction.
     *
     * @param bool $conjunction
     */
    public function setConjunction($conjunction)
    {
        $this->entity->setConjunction($conjunction);
    }

    /**
     * Set context.
     *
     * @param $name
     */
    public function setContext($name)
    {
        $this->entity->setContext($name);
    }

    /**
     * Add translations.
     *
     * @param FilterTranslation $translation
     */
    public function addTranslation(FilterTranslation $translation)
    {
        $this->entity->addTranslation($translation);
    }

    /**
     * Remove translations.
     *
     * @param FilterTranslation $translations
     *
     * @return bool
     */
    public function removeTranslation(FilterTranslation $translations)
    {
        return $this->entity->getTranslations()->removeElement($translations);
    }

    /**
     * Get translation by locale.
     *
     * @return FilterTranslation
     */
    private function getTranslation()
    {
        $filterTranslation = null;
        foreach ($this->entity->getTranslations() as $translation) {
            if ($translation->getLocale() == $this->locale) {
                $filterTranslation = $translation;
            }
        }
        if (!$filterTranslation) {
            $filterTranslation = new FilterTranslation();
            $filterTranslation->setLocale($this->locale);
            $filterTranslation->setFilter($this->entity);
            $this->entity->addTranslation($filterTranslation);
        }

        return $filterTranslation;
    }

    /**
     * Sets the name of the filter.
     *
     * @param string $name The name of the filter
     */
    public function setName($name)
    {
        $this->getTranslation()->setName($name);
    }

    /**
     * Add conditionGroups.
     *
     * @param ConditionGroup $conditionGroup
     *
     * @return Filter
     */
    public function addConditionGroup(ConditionGroup $conditionGroup)
    {
        return $this->entity->addConditionGroup($conditionGroup->getEntity());
    }

    /**
     * Remove conditionGroup.
     *
     * @param ConditionGroup $conditionGroup
     */
    public function removeConditionGroup(ConditionGroup $conditionGroup)
    {
        $this->entity->removeConditionGroup($conditionGroup->getEntity());
    }

    /**
     * Sets the changer of the filter.
     *
     * @param UserInterface $user The changer of the filter
     */
    public function setChanger(UserInterface $user)
    {
        $this->entity->setChanger($user);
    }

    /**
     * Sets the changer of the filter.
     *
     * @return UserInterface creator/owner filter
     */
    public function getChanger()
    {
        return $this->entity->getChanger();
    }

    /**
     * Sets the creator of the filter.
     *
     * @param UserInterface $user The creator of the filter
     */
    public function setCreator(UserInterface $user)
    {
        $this->entity->setCreator($user);
    }

    /**
     * Returns the creator of the filter.
     *
     * @return UserInterface creator/owner of the filter
     */
    public function getCreator()
    {
        return $this->entity->getCreator();
    }

    /**
     * Sets the change time of the filter.
     *
     * @param \DateTime $changed
     */
    public function setChanged(\DateTime $changed)
    {
        $this->entity->setChanged($changed);
    }

    /**
     * Sets the created time of the filter.
     *
     * @param \DateTime $created
     */
    public function setCreated(\DateTime $created)
    {
        $this->entity->setCreated($created);
    }

    /**
     * Sets the change time of the filter.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Sets the created time of the filter.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * Set user.
     *
     * @param UserInterface $user
     *
     * @return Filter
     */
    public function setUser(UserInterface $user = null)
    {
        $this->entity->setUser($user);
    }

    /**
     * Get user.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->entity->getUser();
    }

    /**
     * Get private flag.
     *
     * @VirtualProperty
     * @SerializedName("private")
     *
     * @return bool
     */
    public function getPrivate()
    {
        return $this->entity->getPrivate();
    }

    /**
     * Sets the private flag.
     *
     * @param $private boolean
     *
     * @return FilterEntity
     */
    public function setPrivate($private)
    {
        $this->entity->setPrivate($private);
    }
}
