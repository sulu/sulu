<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Coduo\PHPHumanizer\String;
use Doctrine\ORM\ORMException;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Handles configuration for system collections.
 */
class SystemCollectionCompilerPass implements CompilerPassInterface
{
    /**
     * @var CollectionManagerInterface
     */
    private $collectionManager;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $locale = 'en'; // TODO which locale? maybe config default locale
        $userId = 1; // TODO use correct user-id

        try {
            $this->collectionManager = $container->get('sulu_media.collection_manager');
            $entityManager = $container->get('doctrine.orm.entity_manager');
            $config = $container->getParameter('sulu_media.system_collections');

            $root = $this->getOrCreateNamespace('system_collections', $locale, $userId);

            $namespaces = [];
            foreach ($config as $key => $item) {
                if (!array_key_exists($item['namespace'], $namespaces)) {
                    $namespaces[$item['namespace']] = $namespace = $this->getOrCreateNamespace(
                        $item['namespace'],
                        $locale,
                        $userId,
                        $root->getId()
                    );
                }

                $collection = $this->getOrCreateCollection(
                    $key,
                    $item['meta_title'],
                    $userId,
                    $namespaces[$item['namespace']]->getId()
                );
                $container->setParameter($item['parameter'], $collection->getId());
            }

            $entityManager->flush();
        } catch (ORMException $ex) {
            // FIXME Schema is not valid ...
            return;
        }
    }

    private function getOrCreateNamespace($namespace, $locale, $userId, $parent = null)
    {
        if (($collection = $this->collectionManager->getByKey($namespace, $locale)) !== null) {
            $collection->setTitle(String::humanize($namespace));

            return $collection;
        }

        return $this->createCollection(String::humanize($namespace), $namespace, $locale, $userId, $parent);
    }

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

    private function createCollection($title, $key, $locale, $userId, $parent = null, $id = null)
    {
        $data = [
            'title' => $title,
            'key' => $key,
            'type' => ['id' => 2],
            'locale' => $locale
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
