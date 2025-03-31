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
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
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
     * @return string Token can be used to reuse this preview-session
     *
     * @throws ProviderNotFoundException
     */
    public function start(string $providerKey, string $id, int $userId, array $data = [], array $options = []): string
    {
        $locale = $options['locale'] ?? null;
        $provider = $this->getProvider($providerKey);
        $object = $provider->getObject($id, $locale);

        $cacheItem = new PreviewCacheItem($id, $userId, $providerKey, $object);
        if (!empty($data)) {
            $provider->setValues($object, $locale, $data);
        }

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
     * @return string Complete html response
     */
    public function update(
        string $token,
        array $data,
        array $options = []
    ): string {
        $locale = $options['locale'] ?? null;
        $cacheItem = $this->fetch($token);

        $provider = $this->getProvider($cacheItem->getProviderKey());
        if (!empty($data)) {
            $provider->setValues($cacheItem->getObject(), $locale, $data);
            $this->save($cacheItem);
        }

        return $this->renderPartial($cacheItem, $options);
    }

    /**
     * Updates given context and restart preview with given data.
     *
     * @param mixed[] $context
     * @param mixed[] $data
     * @param mixed[] $options
     *
     * @return string Complete html response
     */
    public function updateContext(
        string $token,
        array $context,
        array $data,
        array $options = []
    ): string {
        $locale = $options['locale'] ?? null;
        $cacheItem = $this->fetch($token);

        $provider = $this->getProvider($cacheItem->getProviderKey());
        if (!empty($data)) {
            $provider->setValues($cacheItem->getObject(), $locale, $data);
        }

        if (0 === \count($context)) {
            return $this->renderer->render(
                $cacheItem->getObject(),
                $cacheItem->getId(),
                false,
                $options
            );
        }

        $cacheItem->setObject($provider->setContext($cacheItem->getObject(), $locale, $context));

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
     * Returns rendered preview-session.
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

    protected function getProvider(string $providerKey): PreviewObjectProviderInterface
    {
        return $this->previewObjectProviderRegistry->getPreviewObjectProvider($providerKey);
    }

    protected function save(PreviewCacheItem $item): void
    {
        $data = [
            'id' => $item->getId(),
            'userId' => $item->getUserId(),
            'providerKey' => $item->getProviderKey(),
            'html' => $item->getHtml(),
            'object' => $this->getProvider($item->getProviderKey())->serialize($item->getObject()),
            'objectClass' => \get_class($item->getObject()),
        ];

        $this->cache->save($item->getToken(), \json_encode($data), $this->cacheLifeTime);
    }

    protected function fetch(string $token): PreviewCacheItem
    {
        if (!$this->exists($token)) {
            throw new TokenNotFoundException($token);
        }

        $data = \json_decode($this->cache->fetch($token), true);
        $provider = $this->getProvider($data['providerKey']);

        $cacheItem = new PreviewCacheItem(
            $data['id'],
            $data['userId'],
            $data['providerKey'],
            $provider->deserialize($data['object'], $data['objectClass'])
        );
        if ($data['html']) {
            $cacheItem->setHtml($data['html']);
        }

        return $cacheItem;
    }
}
