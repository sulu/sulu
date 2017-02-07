<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SystemCollections;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Component\Cache\CacheInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Manages system-collections.
 */
class SystemCollectionManager implements SystemCollectionManagerInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var CollectionManagerInterface
     */
    private $collectionManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenProvider;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $systemCollections;

    public function __construct(
        array $config,
        CollectionManagerInterface $collectionManager,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenProvider = null,
        CacheInterface $cache,
        $locale
    ) {
        $this->config = $config;
        $this->collectionManager = $collectionManager;
        $this->entityManager = $entityManager;
        $this->tokenProvider = $tokenProvider;
        $this->cache = $cache;
        $this->locale = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp()
    {
        $this->getSystemCollections();
    }

    /**
     * {@inheritdoc}
     */
    public function getSystemCollection($key)
    {
        $systemCollections = $this->getSystemCollections();

        if (!array_key_exists($key, $systemCollections)) {
            throw new UnrecognizedSystemCollection($key, array_keys($systemCollections));
        }

        return $systemCollections[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function isSystemCollection($id)
    {
        return in_array($id, $this->getSystemCollections());
    }

    /**
     * Returns system collections.
     *
     * @return array
     */
    private function getSystemCollections()
    {
        if (!$this->systemCollections) {
            if (!$this->cache->isFresh()) {
                $systemCollections = $this->buildSystemCollections(
                    $this->locale,
                    $this->getUserId()
                );

                $this->cache->write($systemCollections);
            }

            $this->systemCollections = $this->cache->read();
        }

        return $this->systemCollections;
    }

    /**
     * Returns current user.
     *
     * @return int
     */
    private function getUserId()
    {
        if (!$this->tokenProvider || ($token = $this->tokenProvider->getToken()) === null) {
            return;
        }

        if (!$token->getUser() instanceof UserInterface) {
            return;
        }

        return $token->getUser()->getId();
    }

    /**
     * Go thru configuration and build all system collections.
     *
     * @param string $locale
     * @param int $userId
     *
     * @return array
     */
    private function buildSystemCollections($locale, $userId)
    {
        $root = $this->getOrCreateRoot(SystemCollectionManagerInterface::COLLECTION_KEY, 'System', $locale, $userId);
        $collections = ['root' => $root->getId()];
        $collections = array_merge($collections, $this->iterateOverCollections($this->config, $userId, $root->getId()));

        $this->entityManager->flush();

        return $collections;
    }

    /**
     * Iterates over an array of children collections, creates them.
     * This function is recursive!
     *
     * @param $children
     * @param $userId
     * @param null $parent
     * @param string $namespace
     *
     * @return array
     */
    private function iterateOverCollections($children, $userId, $parent = null, $namespace = '')
    {
        $format = ($namespace !== '' ? '%s.%s' : '%s%s');
        $collections = [];
        foreach ($children as $collectionKey => $collectionItem) {
            $key = sprintf($format, $namespace, $collectionKey);
            $collections[$key] = $this->getOrCreateCollection(
                $key,
                $collectionItem['meta_title'],
                $userId,
                $parent
            )->getId();

            if (array_key_exists('collections', $collectionItem)) {
                $childCollections = $this->iterateOverCollections(
                    $collectionItem['collections'],
                    $userId,
                    $collections[$key],
                    $key
                );
                $collections = array_merge($collections, $childCollections);
            }
        }

        return $collections;
    }

    /**
     * Finds or create a new system-collection namespace.
     *
     * @param string $namespace
     * @param string $title
     * @param string $locale
     * @param int $userId
     * @param int|null $parent id of parent collection or null for root
     *
     * @return Collection
     */
    private function getOrCreateRoot($namespace, $title, $locale, $userId, $parent = null)
    {
        if (($collection = $this->collectionManager->getByKey($namespace, $locale)) !== null) {
            $collection->setTitle($title);

            return $collection;
        }

        return $this->createCollection($title, $namespace, $locale, $userId, $parent);
    }

    /**
     * Finds or create a new system-collection.
     *
     * @param string $key
     * @param array $localizedTitles
     * @param int $userId
     * @param int|null $parent id of parent collection or null for root
     *
     * @return Collection
     */
    private function getOrCreateCollection($key, $localizedTitles, $userId, $parent)
    {
        $locales = array_keys($localizedTitles);
        $firstLocale = array_shift($locales);

        $collection = $this->collectionManager->getByKey($key, $firstLocale);
        if ($collection === null) {
            $collection = $this->createCollection($localizedTitles[$firstLocale], $key, $firstLocale, $userId, $parent);
        } else {
            $collection->setTitle($localizedTitles[$firstLocale]);
        }

        foreach ($locales as $locale) {
            $this->createCollection($localizedTitles[$locale], $key, $locale, $userId, $parent, $collection->getId());
        }

        return $collection;
    }

    /**
     * Creates a new collection.
     *
     * @param string $title
     * @param string $key
     * @param string $locale
     * @param int $userId
     * @param int|null $parent id of parent collection or null for root
     * @param int|null $id if not null a colleciton will be updated
     *
     * @return Collection
     */
    private function createCollection($title, $key, $locale, $userId, $parent = null, $id = null)
    {
        $data = [
            'title' => $title,
            'key' => $key,
            'type' => ['id' => 2],
            'locale' => $locale,
        ];

        if ($parent !== null) {
            $data['parent'] = $parent;
        }

        if ($id !== null) {
            $data['id'] = $id;
        }

        return $this->collectionManager->save($data, $userId);
    }
}
