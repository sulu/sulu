<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

use Doctrine\Common\Cache\Cache;
use Psr\Cache\CacheItemPoolInterface;
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class Preview
{
    public const CONTENT_REPLACER = '<!-- CONTENT-REPLACER -->';

    /**
     * @var PreviewCache
     */
    private $cache;

    /**
     * @param CacheItemPoolInterface|Cache $cache
     */
    public function __construct(
        private PreviewObjectProviderRegistryInterface $previewObjectProviderRegistry,
        $cache,
        private PreviewRendererInterface $renderer,
        private int $cacheLifeTime = 3600
    ) {
        $this->cache = new PreviewCache($cache);
    }

    /**
     * Starts a new preview session.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     *
     * @return string Token can be used to reuse this preview-session
     *
     * @throws ProviderNotFoundException
     */
    public function start(string $providerKey, string $id, int $userId, array $data = [], array $options = []): string
    {
        /** @var string $locale */
        $locale = $options['locale']; // TODO think we should add locale as required parameter not over options array
        $provider = $this->getProvider($providerKey);

        $previewContext = new PreviewContext($id, $locale);

        /** @var array<string, mixed> $object */
        $object = $provider->getDefaults($previewContext);

        if (!empty($data)) {
            $object = $provider->updateValues($previewContext, $object, $data);
        }

        $cacheItem = new PreviewCacheItem($id, $locale, $userId, $providerKey, $object);
        $this->save($cacheItem);

        return $cacheItem->getToken();
    }

    /**
     * Stops the preview-session and deletes the data.
     */
    public function stop(string $token): void
    {
        if (!$this->exists($token)) {
            return;
        }

        $this->cache->delete($token);
    }

    /**
     * Returns true if such a session exists.
     */
    public function exists(string $token): bool
    {
        return $this->cache->contains($token);
    }

    /**
     * Updates given data in the preview-session.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     *
     * @return string Complete html response
     */
    public function update(
        string $token,
        array $data,
        array $options = []
    ): string {
        /** @var string $locale */
        $locale = $options['locale'] ?? null; // TODO think we should add locale as required parameter not over options array
        $cacheItem = $this->fetch($token);
        $id = $cacheItem->getId();

        $provider = $this->getProvider($cacheItem->getProviderKey());
        if (!empty($data)) {
            $defaults = $cacheItem->getObject();
            $previewContext = new PreviewContext($id, $locale);
            $object = $provider->updateValues($previewContext, $defaults, $data);
            $cacheItem->setObject($object);

            $this->save($cacheItem);
        }

        return $this->renderPartial($cacheItem, $options);
    }

    /**
     * Updates given context and restart preview with given data.
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     *
     * @return string Complete html response
     */
    public function updateContext(
        string $token,
        array $context,
        array $data,
        array $options = []
    ): string {
        /** @var string $locale */
        $locale = $options['locale'] ?? null; // TODO think we should add locale as required parameter not over options array
        $cacheItem = $this->fetch($token);

        $previewContext = new PreviewContext($cacheItem->getId(), $locale);
        $provider = $this->getProvider($cacheItem->getProviderKey());
        $object = $cacheItem->getObject();
        if (!empty($data)) {
            /** @var array<string, mixed> $defaults */
            $defaults = $object;
            $object = $provider->updateValues($previewContext, $defaults, $data);
        }

        if (0 === \count($context)) {
            return $this->renderer->render(
                $object,
                $cacheItem->getId(),
                false,
                $options
            );
        }

        $defaults = $cacheItem->getObject();
        $object = $provider->updateContext($previewContext, $defaults, $context);

        $cacheItem->setObject($object);

        $html = $this->renderer->render(
            $object,
            $cacheItem->getId(),
            false,
            $options
        );

        $cacheItem->setHtml($this->removeContent($html));
        $this->save($cacheItem);

        return $this->renderPartial($cacheItem, $options);
    }

    /**
     * Returns rendered preview-session.
     *
     * @param array<string, mixed> $options
     *
     * @return string Complete html response
     */
    public function render(
        string $token,
        array $options = []
    ): string {
        $cacheItem = $this->fetch($token);

        $html = $this->renderer->render(
            $cacheItem->getObject(),
            $cacheItem->getId(),
            false,
            $options
        );

        $cacheItem->setHtml($this->removeContent($html));
        $this->save($cacheItem);

        return $this->renderPartial($cacheItem, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function renderPartial(
        PreviewCacheItem $cacheItem,
        array $options = []
    ): string {
        $partialHtml = $this->renderer->render(
            $cacheItem->getObject(),
            $cacheItem->getId(),
            true,
            $options
        );

        return \str_replace(self::CONTENT_REPLACER, $partialHtml, $cacheItem->getHtml());
    }

    protected function removeContent(string $html): string
    {
        $parts = \explode(self::CONTENT_REPLACER, $html);

        if (!isset($parts[2])) {
            throw new \RuntimeException('The "{% block content %}" could not be found in the twig template.');
        }

        return $parts[0] . self::CONTENT_REPLACER . $parts[2];
    }

    protected function getProvider(string $providerKey): PreviewDefaultsProviderInterface
    {
        return $this->previewObjectProviderRegistry->getPreviewObjectProvider($providerKey);
    }

    protected function save(PreviewCacheItem $item): void
    {
        $object = $item->getObject();
        $objectType = \get_debug_type($object);

        $data = [
            'id' => $item->getId(),
            'locale' => $item->getLocale(),
            'userId' => $item->getUserId(),
            'providerKey' => $item->getProviderKey(),
            'html' => $item->getHtml(),
            'object' => $object,
            'objectClass' => $objectType,
        ];

        $this->cache->save($item->getToken(), \json_encode($data), $this->cacheLifeTime);
    }

    protected function fetch(string $token): PreviewCacheItem
    {
        if (!$this->exists($token)) {
            throw new TokenNotFoundException($token);
        }

        /**
         * @var array{
         *     id: string,
         *     locale: string,
         *     userId: int,
         *     providerKey: string,
         *     html: string|null,
         *     object: mixed,
         *     objectClass: string,
         * } $data
         */
        $data = \json_decode($this->cache->fetch($token), true);
        $provider = $this->getProvider($data['providerKey']);

        $object = $provider->getDefaults(new PreviewContext($data['id'], $data['locale']));

        $cacheItem = new PreviewCacheItem(
            $data['id'],
            $data['locale'],
            $data['userId'],
            $data['providerKey'],
            $object,
        );

        if ($data['html']) {
            $cacheItem->setHtml($data['html']);
        }

        return $cacheItem;
    }
}
