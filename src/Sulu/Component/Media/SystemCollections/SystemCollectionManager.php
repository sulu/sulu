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

use Coduo\PHPHumanizer\String;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Symfony\Component\Config\ConfigCache;
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
     * @var string
     */
    private $cachePath;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var array
     */
    private $systemCollections;

    public function __construct(
        array $config,
        CollectionManagerInterface $collectionManager,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenProvider,
        $locale,
        $cachePath,
        $debug
    ) {
        $this->config = $config;
        $this->collectionManager = $collectionManager;
        $this->entityManager = $entityManager;
        $this->tokenProvider = $tokenProvider;
        $this->locale = $locale;
        $this->cachePath = $cachePath;
        $this->debug = $debug;
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

        return $systemCollections[$key];
    }

    /**
     * Returns system collections.
     *
     * @return array
     */
    private function getSystemCollections()
    {
        if (!$this->systemCollections) {
            $cache = new ConfigCache($this->cachePath, $this->debug);
            if (!$cache->isFresh()) {
                $systemCollections = $this->buildSystemCollections(
                    $this->locale,
                    $this->tokenProvider->getToken()->getUser()
                );

                $cache->write(serialize($systemCollections));
            }

            $this->systemCollections = unserialize(file_get_contents($this->cachePath));
        }

        return $this->systemCollections;
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
        $root = $this->getOrCreateNamespace('system_collections', $locale, $userId);

        $namespaces = [];
        $collections = [];
        foreach ($this->config as $key => $item) {
            if (!array_key_exists($item['namespace'], $namespaces)) {
                $namespaces[$item['namespace']] = $namespace = $this->getOrCreateNamespace(
                    $item['namespace'],
                    $locale,
                    $userId,
                    $root->getId()
                );
            }

            $collections[$key] = $this->getOrCreateCollection(
                $key,
                $item['meta_title'],
                $userId,
                $namespaces[$item['namespace']]->getId()
            )->getId();
        }

        $this->entityManager->flush();

        return $collections;
    }

    /**
     * Finds or create a new system-collection namespace.
     *
     * @param string $namespace
     * @param string $locale
     * @param int $userId
     * @param int|null $parent id of parent collection or null for root.
     *
     * @return Collection
     */
    private function getOrCreateNamespace($namespace, $locale, $userId, $parent = null)
    {
        if (($collection = $this->collectionManager->getByKey($namespace, $locale)) !== null) {
            $collection->setTitle(String::humanize($namespace));

            return $collection;
        }

        return $this->createCollection(String::humanize($namespace), $namespace, $locale, $userId, $parent);
    }

    /**
     * Finds or create a new system-collection.
     *
     * @param string $key
     * @param array $localizedTitles
     * @param int $userId
     * @param int|null $parent id of parent collection or null for root.
     *
     * @return Collection
     */
    private function getOrCreateCollection($key, $localizedTitles, $userId, $parent)
    {
        $locales = array_keys($localizedTitles);
        $firstLocale = array_pop($locales);

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
     * @param int|null $parent id of parent collection or null for root.
     * @param int|null $id if not null a colleciton will be updated.
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
