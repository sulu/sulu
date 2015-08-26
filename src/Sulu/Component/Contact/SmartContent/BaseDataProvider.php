<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\SmartContent\Orm\BaseDataProvider as SmartContentBaseDataProvider;

/**
 * Basic functionality for account/contact serialization.
 */
abstract class BaseDataProvider extends SmartContentBaseDataProvider
{
    /**
     * Extracts emails from entity.
     *
     * @param Contact|Account $entity
     *
     * @return array
     */
    protected function getEmails($entity)
    {
        $emails = [];
        foreach ($entity->getEmails() as $email) {
            /** @var Email $email */
            $emails[] = ['email' => $email->getEmail(), 'type' => $email->getEmailType()];
        }

        return $emails;
    }

    /**
     * Extracts phones from entity.
     *
     * @param Contact|Account $entity
     *
     * @return array
     */
    protected function getPhones($entity)
    {
        $phones = [];
        foreach ($entity->getPhones() as $phone) {
            /** @var Phone $phone */
            $phones[] = ['phone' => $phone->getPhone(), 'type' => $phone->getPhoneType()];
        }

        return $phones;
    }

    /**
     * Extracts faxes from entity.
     *
     * @param Contact|Account $entity
     *
     * @return array
     */
    protected function getFaxes($entity)
    {
        $faxes = [];
        foreach ($entity->getFaxes() as $fax) {
            /** @var Fax $fax */
            $faxes[] = ['fax' => $fax->getFax(), 'type' => $fax->getFaxType()];
        }

        return $faxes;
    }

    /**
     * Extracts urls from entity.
     *
     * @param Contact|Account $entity
     *
     * @return array
     */
    protected function getUrls($entity)
    {
        $urls = [];
        foreach ($entity->getUrls() as $url) {
            /** @var Url $url */
            $urls[] = ['url' => $url->getUrl(), 'type' => $url->getUrlType()];
        }

        return $urls;
    }

    /**
     * Extracts tags from entity.
     *
     * @param Contact|Account $entity
     *
     * @return array
     */
    protected function getTags($entity)
    {
        $tags = [];
        foreach ($entity->getTags() as $tag) {
            /** @var Tag $tag */
            $tags[] = $tag->getName();
        }

        return $tags;
    }

    /**
     * Extracts categories from entity.
     *
     * @param Contact|Account $entity
     * @param string $locale
     *
     * @return array
     */
    protected function getCategories($entity, $locale)
    {
        $categories = [];
        foreach ($entity->getCategories() as $category) {
            /** @var Category $category */
            $translation = $this->getCategoryTranslation($category, $locale);

            $categories[] = $translation->getTranslation();
        }

        return $categories;
    }

    /**
     * Returns translation for given locale.
     *
     * @param Category $category
     * @param string $locale
     *
     * @return CategoryTranslation
     */
    protected function getCategoryTranslation(Category $category, $locale)
    {
        foreach ($category->getTranslations() as $translation) {
            if ($translation->getLocale() == $locale) {
                return $translation;
            }
        }

        return $category->getTranslations()->first();
    }
}
