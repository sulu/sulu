<?php
/*
 * This file is part Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\SmartContent;

use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;

/**
 * Contact DataProvider for SmartContent.
 */
class ContactDataProvider implements DataProviderInterface
{
    /**
     * @var ProviderConfigurationInterface
     */
    private $configuration;

    /**
     * @var ContactRepository
     */
    private $repository;

    public function __construct(ContactRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if (!$this->configuration) {
            return $this->initConfiguration();
        }

        return $this->configuration;
    }

    /**
     * Initiate configuration.
     *
     * @return ProviderConfigurationInterface
     */
    private function initConfiguration()
    {
        $this->configuration = new ProviderConfiguration();
        $this->configuration->setTags(true);
        $this->configuration->setCategories(false);
        $this->configuration->setLimit(true);
        $this->configuration->setPresentAs(true);
        $this->configuration->setPaginated(true);

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        list($result, $hasNextPage) = $this->resolveFilters($filters, $limit, $page, $pageSize);

        return new DataProviderResult($this->decorateDataItems($result), $hasNextPage);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        list($result, $hasNextPage) = $this->resolveFilters($filters, $limit, $page, $pageSize);

        return new DataProviderResult($this->decorateResourceItems($result, $options['locale']), $hasNextPage);
    }

    /**
     * Resolves filters.
     */
    private function resolveFilters(
        array $filters,
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        $result = $this->repository->findByFilters($filters, $page, $pageSize, $limit);

        $hasNextPage = false;
        if ($pageSize !== null && count($result) > $pageSize) {
            $hasNextPage = true;
            $result = array_splice($result, 0, $pageSize);
        }

        return [$result, $hasNextPage];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return;
    }

    /**
     * Decorates result as data item.
     *
     * @param array $data
     *
     * @return array
     */
    private function decorateDataItems(array $data)
    {
        return array_map(
            function ($item) {
                return new ContactDataItem($item);
            },
            $data
        );
    }

    /**
     * Decorates result as resource item.
     *
     * @param array $data
     * @param string $locale
     *
     * @return array
     */
    private function decorateResourceItems(array $data, $locale)
    {
        return array_map(
            function (Contact $item) use ($locale) {
                $itemData = $this->contactToArray($item, $locale);

                return new ArrayAccessItem($item->getId(), $itemData, $item);
            },
            $data
        );
    }

    /**
     * Converts contact entity to array.
     *
     * @param Contact $contact
     * @param string $locale
     *
     * @return array
     */
    private function contactToArray(Contact $contact, $locale)
    {
        $emails = [];
        foreach ($contact->getEmails() as $email) {
            /** @var Email $email */
            $emails[] = ['email' => $email->getEmail(), 'type' => $email->getEmailType()];
        }
        $phones = [];
        foreach ($contact->getPhones() as $phone) {
            /** @var Phone $phone */
            $phones[] = ['phone' => $phone->getPhone(), 'type' => $phone->getPhoneType()];
        }
        $faxes = [];
        foreach ($contact->getFaxes() as $fax) {
            /** @var Fax $fax */
            $faxes[] = ['fax' => $fax->getFax(), 'type' => $fax->getFaxType()];
        }
        $urls = [];
        foreach ($contact->getUrls() as $url) {
            /** @var Url $url */
            $urls[] = ['url' => $url->getUrl(), 'type' => $url->getUrlType()];
        }
        $tags = [];
        foreach ($contact->getTags() as $tag) {
            /** @var Tag $tag */
            $tags[] = $tag->getName();
        }
        $categories = [];
        foreach ($contact->getCategories() as $category) {
            /** @var Category $category */
            $translation = $this->getCategoryTranslation($category, $locale);

            $categories[] = $translation->getTranslation();
        }

        return [
            'formOfAddress' => $contact->getFormOfAddress(),
            'title' => $contact->getTitle(),
            'salutation' => $contact->getSalutation(),
            'fullName' => $contact->getFullName(),
            'firstName' => $contact->getFirstName(),
            'lastName' => $contact->getLastName(),
            'middleName' => $contact->getMiddleName(),
            'birthday' => $contact->getBirthday(),
            'created' => $contact->getCreated(),
            'creator' => $contact->getCreator(),
            'changed' => $contact->getChanged(),
            'changer' => $contact->getChanger(),
            'medias' => $contact->getMedias(),
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
    private function getCategoryTranslation(Category $category, $locale)
    {
        foreach ($category->getTranslations() as $translation) {
            if ($translation->getLocale() == $locale) {
                return $translation;
            }
        }

        return $category->getTranslations()->first();
    }
}
