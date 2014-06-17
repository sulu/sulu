<?php


namespace Sulu\Bundle\CategoryBundle\Api;

use Sulu\Bundle\CategoryBundle\Entity\Category as Entity;
use Sulu\Bundle\CoreBundle\Entity\ApiEntityWrapper;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;


class Category extends ApiEntityWrapper
{

    public function __construct(Entity $category, $locale)
    {
        $this->entity = $category;
        $this->locale = $locale;
    }

    /**
     * Returns the id of the category
     * @VirtualProperty
     * @SerializedName("id")
     * @return array
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Returns the name of the Category dependent on the locale
     * @VirtualProperty
     * @SerializedName("name")
     * @return string
     */
    public function getName()
    {
        $translations = $this->entity->getTranslations();
        $name = $translations[0]->getTranslation();
        foreach ($translations as $translation) {
            if ($translation->getLocale() === $this->locale) {
                $name = $translation->getTranslation();
                break;
            }
        }
        return $name;
    }

    /**
     * Returns the name of the Category dependent on the locale
     * @VirtualProperty
     * @SerializedName("meta")
     * @return array
     */
    public function getMeta()
    {
        $arrReturn = [];
        foreach ($this->entity->getMeta() as $meta) {
            if (!$meta->getLocale() || $meta->getLocale() === $this->locale) {
                array_push($arrReturn, [
                    'id' => $meta->getId(),
                    'key' => $meta->getKey(),
                    'value' => $meta->getValue(),
                ]);
            }
        }
        return $arrReturn;
    }

    /**
     * Returns the creator of the category
     * @VirtualProperty
     * @SerializedName("creator")
     * @return array
     */
    public function getCreator()
    {
        $creator = $this->entity->getCreator();
        if ($creator) {
            return [
                'id' => $creator->getId(),
            ];
        } else {
            return [];
        }
    }

    /**
     * Returns the changer of the category
     * @VirtualProperty
     * @SerializedName("changer")
     * @return array
     */
    public function getChanger()
    {
        $changer = $this->entity->getChanger();
        if ($changer) {
            return [
                'id' => $changer->getId(),
            ];
        } else {
            return [];
        }
    }
}
