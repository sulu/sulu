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
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;

/**
 * Contact DataProvider for SmartContent.
 */
class ContactDataProvider extends BaseDataProvider
{
    public function __construct(DataProviderRepositoryInterface $repository)
    {
        parent::__construct($repository);

        $this->configuration = $this->initConfiguration(true, false, true, true, true, []);
    }

    /**
     * {@inheritdoc}
     */
    protected function decorateDataItems(array $data)
    {
        return array_map(
            function ($item) {
                return new ContactDataItem($item);
            },
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function convertToArray($entity, $locale)
    {
        $emails = [];
        foreach ($entity->getEmails() as $email) {
            /** @var Email $email */
            $emails[] = ['email' => $email->getEmail(), 'type' => $email->getEmailType()];
        }
        $phones = [];
        foreach ($entity->getPhones() as $phone) {
            /** @var Phone $phone */
            $phones[] = ['phone' => $phone->getPhone(), 'type' => $phone->getPhoneType()];
        }
        $faxes = [];
        foreach ($entity->getFaxes() as $fax) {
            /** @var Fax $fax */
            $faxes[] = ['fax' => $fax->getFax(), 'type' => $fax->getFaxType()];
        }
        $urls = [];
        foreach ($entity->getUrls() as $url) {
            /** @var Url $url */
            $urls[] = ['url' => $url->getUrl(), 'type' => $url->getUrlType()];
        }
        $tags = [];
        foreach ($entity->getTags() as $tag) {
            /** @var Tag $tag */
            $tags[] = $tag->getName();
        }
        $categories = [];
        foreach ($entity->getCategories() as $category) {
            /** @var Category $category */
            $translation = $this->getCategoryTranslation($category, $locale);

            $categories[] = $translation->getTranslation();
        }

        return [
            'formOfAddress' => $entity->getFormOfAddress(),
            'title' => $entity->getTitle(),
            'salutation' => $entity->getSalutation(),
            'fullName' => $entity->getFullName(),
            'firstName' => $entity->getFirstName(),
            'lastName' => $entity->getLastName(),
            'middleName' => $entity->getMiddleName(),
            'birthday' => $entity->getBirthday(),
            'created' => $entity->getCreated(),
            'creator' => $entity->getCreator(),
            'changed' => $entity->getChanged(),
            'changer' => $entity->getChanger(),
            'medias' => $entity->getMedias(),
            'emails' => $emails,
            'phones' => $phones,
            'faxes' => $faxes,
            'urls' => $urls,
            'tags' => $tags,
            'categories' => $categories,
        ];
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
