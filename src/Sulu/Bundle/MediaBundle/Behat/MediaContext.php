<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TestBundle\Behat\BaseContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Behat context class for the MediaBundle.
 */
class MediaContext extends BaseContext implements SnippetAcceptingContext
{
    /**
     * @var Collection
     */
    private $lastCollection;

    /**
     * @Given the media collection ":name" exists
     */
    public function theMediaCollectionExists($name)
    {
        $this->getOrCreateMediaCollection($name);
    }

    /**
     * @Then the media collection ":name" should exist
     */
    public function theMediaCollectionShouldExist($name)
    {
        $meta = $this->getEntityManager()
            ->getRepository('Sulu\Bundle\MediaBundle\Entity\CollectionMeta')
            ->findOneByTitle($name);

        if (!$meta) {
            throw new \InvalidArgumentException(sprintf(
                'Collection "%s" should exist'
            ));
        }
    }

    /**
     * @Then the media collection ":name" should not exist
     */
    public function theMediaCollectionShouldNotExist($name)
    {
        $meta = $this->getEntityManager()
            ->getRepository('Sulu\Bundle\MediaBundle\Entity\CollectionMeta')
            ->findOneByTitle($name);

        if ($meta) {
            throw new \InvalidArgumentException(sprintf(
                'Collection "%s" should not exist'
            ));
        }
    }

    /**
     * @Given the file ":name" has been uploaded to the ":collectionName" collection
     */
    public function theFileHasBeenUploaded($name, $collectionName)
    {
        $path = __DIR__ . '/images/' . $name;
        $file = new UploadedFile($path, $path, null, null, null, true);
        $collection = $this->getOrCreateMediaCollection($collectionName);
        $mediaType = $this->getOrCreateMediaType('image');

        $data = [
            'id' => null,
            'locale' => 'en',
            'type' => $mediaType->getId(),
            'collection' => $collection->getId(),
            'name' => basename($name),
            'title' => basename($name),
        ];

        $this->getMediaManager()->save($file, $data, $this->getUserId());
    }

    /**
     * @Given I am on the media collection edit page
     * @Given I am editing the media collection ":name"
     */
    public function iAmEditingTheMediaCollection($name = null)
    {
        if ($name) {
            $collection = $this->getOrCreateMediaCollection($name);
        } else {
            $collection = $this->getLastMediaCollection();
        }

        $this->visitPath('/admin/#media/collections/edit:' . $collection->getId() . '/files');
        $this->waitForAuraEvents(['husky.datagrid.view.rendered']);
    }

    /**
     * @Given I expect a thumbnail to appear
     */
    public function iWaitForAThumbnailToAppear()
    {
        $this->waitForSelectorAndAssert('.thumbnail');
    }

    /**
     * @Given I expect an item to appear
     */
    public function iWaitForAnItemToAppear()
    {
        $this->waitForSelectorAndAssert('.husky-thumbnails .item');
    }

    /**
     * @Then I should see ":count" items
     */
    public function iShouldSeeItems($count)
    {
        $actual = $this->getSession()->evaluateScript('$(".husky-thumbnails .item").length');

        if ($actual != $count) {
            throw new \InvalidArgumentException(sprintf(
                'Expected "%s" items but got "%s"', $count, $actual
            ));
        }
    }

    /**
     * @When I attach the file ":path" to the current drop-zone
     */
    public function iAttachTheFileToTheCurrentDropZone($path)
    {
        if ($this->getMinkParameter('files_path')) {
            $fullPath = rtrim(
                    realpath($this->getMinkParameter('files_path')),
                    DIRECTORY_SEPARATOR
                ) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        } else {
            $fullPath = __DIR__ . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }

        if (!is_file($fullPath)) {
            throw new \InvalidArgumentException(sprintf('File doesn\'t exist (%s)', $fullPath));
        }

        $fields = $this->getSession()->getPage()->findAll('css', 'input[type="file"]');

        if (count($fields) == 0) {
            throw new ElementNotFoundException($this->getSession(), 'drop-zone upload field');
        }

        /** @var NodeElement $field */
        $field = end($fields);
        $field->attachFile($fullPath);
    }

    /**
     * Return the last media collection that was created
     * in this context.
     *
     * @return Collection
     */
    private function getLastMediaCollection()
    {
        if (!$this->lastCollection) {
            throw new \InvalidArgumentException(
                'No media collection has previously been created in this session, cannot use getLastMediaCollection'
            );
        }

        return $this->lastCollection;
    }

    /**
     * Return the media manager.
     *
     * @return MediaManagerInterface
     */
    private function getMediaManager()
    {
        return $this->getService('sulu_media.media_manager');
    }

    /**
     * Get or create a collection type.
     *
     * @param string $name Name of collection type to get or create
     *
     * @return CollectionType
     */
    private function getOrCreateCollectionType($name)
    {
        $manager = $this->getEntityManager();
        $collectionType = $manager->getRepository('SuluMediaBundle:CollectionType')->findOneByName($name);

        if (!$collectionType) {
            $collectionType = new CollectionType();
            $collectionType->setName($name);
            $manager->persist($collectionType);
        }

        return $collectionType;
    }

    /**
     * Get or create a media type.
     *
     * @param string $name Name of media type to created or retrieved
     *
     * @return MediaType
     */
    private function getOrCreateMediaType($name)
    {
        $manager = $this->getEntityManager();
        $collectionType = $manager->getRepository('SuluMediaBundle:MediaType')->findOneByName($name);

        if (!$collectionType) {
            $collectionType = new MediaType();
            $collectionType->setName($name);
            $manager->persist($collectionType);
        }

        return $collectionType;
    }

    /**
     * Get or create a media collection.
     *
     * @param string $name Name of collection to get or create
     *
     * @return Collection
     */
    private function getOrCreateMediaCollection($name)
    {
        $manager = $this->getEntityManager();

        $collectionMeta = $manager->getRepository('SuluMediaBundle:CollectionMeta')->findOneByTitle($name);

        if ($collectionMeta) {
            return $collectionMeta->getCollection();
        }

        $collection = new Collection();
        $collection->setType($this->getOrCreateCollectionType('Default'));
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle($name);
        $collectionMeta->setDescription('Dies ist eine Test Beschreibung');
        $collectionMeta->setLocale('en');
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        $manager->persist($collection);
        $manager->persist($collectionMeta);
        $manager->flush();

        $this->lastCollection = $collection;

        return $collection;
    }
}
