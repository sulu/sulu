<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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

class SystemCollectionManager implements SystemCollectionManagerInterface
{
    /**
     * @var array
     */
    private $systemCollections;

    /**
     * @param string $locale
     */
    public function __construct(
        private array $config,
        private CollectionManagerInterface $collectionManager,
        private EntityManagerInterface $entityManager,
        private ?TokenStorageInterface $tokenProvider,
        private CacheInterface $cache,
        private $locale,
    ) {
    }

    public function warmUp()
    {
        $this->cache->invalidate();
        $this->getSystemCollections();
    }

    public function getSystemCollection($key)
    {
        $systemCollections = $this->getSystemCollections();

        if (!\array_key_exists($key, $systemCollections)) {
            throw new UnrecognizedSystemCollection($key, \array_keys($systemCollections));
        }

        return $systemCollections[$key];
    }

    public function isSystemCollection($id)
    {
        return \in_array($id, $this->getSystemCollections());
    }

    /**
     * Returns system collections.
     */
    private function getSystemCollections(): array
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
     */
    private function getUserId(): ?int
    {
        if (!$this->tokenProvider || null === ($token = $this->tokenProvider->getToken())) {
            return null;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return null;
        }

        return $user->getId();
    }

    /**
     * Go thru configuration and build all system collections.
     */
    private function buildSystemCollections(string $locale, ?int $userId): array
    {
        $root = $this->getOrCreateRoot(SystemCollectionManagerInterface::COLLECTION_KEY, 'System', $locale, $userId);
        $collections = ['root' => $root->getId()];
        $collections = \array_merge($collections, $this->iterateOverCollections($this->config, $userId, $root->getId()));

        $this->entityManager->flush();

        return $collections;
    }

    /**
     * Iterates over an array of children collections, creates them.
     * This function is recursive!
     */
    private function iterateOverCollections(array $children, ?int $userId, ?int $parent = null, string $namespace = ''): array
    {
        $format = ('' !== $namespace ? '%s.%s' : '%s%s');
        $collections = [];
        foreach ($children as $collectionKey => $collectionItem) {
            $key = \sprintf($format, $namespace, $collectionKey);

            /** @var array<string, string> $titles */
            $titles = $collectionItem['meta_title'];
            $collections[$key] = $this->getOrCreateCollection(
                $key,
                $titles,
                $userId,
                $parent
            )->getId();

            if (\array_key_exists('collections', $collectionItem)) {
                $childCollections = $this->iterateOverCollections(
                    $collectionItem['collections'],
                    $userId,
                    $collections[$key],
                    $key
                );
                $collections = \array_merge($collections, $childCollections);
            }
        }

        return $collections;
    }

    /**
     * Finds or create a new system-collection namespace.
     */
    private function getOrCreateRoot(
        string $namespace,
        string $title,
        string $locale,
        ?int $userId,
        ?int $parent = null,
    ): Collection {
        if (null !== ($collection = $this->collectionManager->getByKey($namespace, $locale))) {
            $collection->setTitle($title);

            return $collection;
        }

        return $this->createOrUpdateCollection($title, $namespace, $locale, $userId, $parent);
    }

    /**
     * Finds or create a new system-collection.
     *
     * @param array<string, string> $localizedTitles
     */
    private function getOrCreateCollection(string $key, array $localizedTitles, ?int $userId, ?int $parent): Collection
    {
        $locales = \array_keys($localizedTitles);
        $firstLocale = \array_shift($locales);
        \assert(null !== $firstLocale);

        $collection = $this->collectionManager->getByKey($key, $firstLocale);
        if (null === $collection) {
            $collection = $this->createOrUpdateCollection($localizedTitles[$firstLocale], $key, $firstLocale, $userId, $parent);
        } else {
            $collection->setTitle($localizedTitles[$firstLocale]);
        }

        foreach ($locales as $locale) {
            $this->createOrUpdateCollection($localizedTitles[$locale], $key, $locale, $userId, $parent, $collection->getId());
        }

        return $collection;
    }

    /**
     * Creates or updates a collection collection.
     *
     * ID != null: collection will be updated
     * No ID: collection will be created
     */
    private function createOrUpdateCollection(
        string $title,
        string $key,
        string $locale,
        ?int $userId,
        ?int $parent = null,
        ?int $id = null,
    ): Collection {
        $data = [
            'title' => $title,
            'key' => $key,
            'type' => ['id' => 2],
            'locale' => $locale,
        ];

        if (null !== $parent) {
            $data['parent'] = $parent;
        }

        if (null !== $id) {
            $data['id'] = $id;
        }

        return $this->collectionManager->save($data, $userId);
    }
}
