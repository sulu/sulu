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
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;

class Preview implements PreviewInterface
{
    const CONTENT_REPLACER = '<!-- CONTENT-REPLACER -->';

    /**
     * @var PreviewObjectProviderInterface[]
     */
    private $objectProviders;

    /**
     * @var PreviewRendererInterface
     */
    private $renderer;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var int
     */
    private $cacheLifeTime;

    public function __construct(
        array $objectProviders,
        Cache $cache,
        PreviewRendererInterface $renderer,
        int $cacheLifeTime = 3600
    ) {
        $this->objectProviders = $objectProviders;
        $this->renderer = $renderer;
        $this->cache = $cache;
        $this->cacheLifeTime = $cacheLifeTime;
    }

    public function start(string $providerKey, string $id, string $locale, int $userId, array $data = []): string
    {
        $provider = $this->getProvider($providerKey);
        $object = $provider->getObject($id, $locale);

        $cacheItem = new PreviewCacheItem($id, $locale, $userId, $providerKey, $object);
        if (!empty($data)) {
            $provider->setValues($object, $locale, $data);
        }

        $this->save($cacheItem);

        return $cacheItem->getToken();
    }

    public function stop(string $token): void
    {
        if (!$this->exists($token)) {
            return;
        }

        $this->cache->delete($token);
    }

    public function exists(string $token): bool
    {
        return $this->cache->contains($token);
    }

    public function update(string $token, string $webspaceKey, array $data, ?int $targetGroupId): string
    {
        $cacheItem = $this->fetch($token);

        $provider = $this->getProvider($cacheItem->getProviderKey());
        if (!empty($data)) {
            $provider->setValues($cacheItem->getObject(), $cacheItem->getLocale(), $data);
            $this->save($cacheItem);
        }

        return $this->renderPartial($cacheItem, $webspaceKey, $targetGroupId);
    }

    public function updateContext(string $token, string $webspaceKey, array $context, ?int $targetGroupId): string
    {
        $cacheItem = $this->fetch($token);

        $provider = $this->getProvider($cacheItem->getProviderKey());
        if (0 === count($context)) {
            return $this->renderer->render(
                $cacheItem->getObject(),
                $cacheItem->getId(),
                $webspaceKey,
                $cacheItem->getLocale(),
                false,
                $targetGroupId
            );
        }

        $cacheItem->setObject($provider->setContext($cacheItem->getObject(), $cacheItem->getLocale(), $context));

        $html = $this->renderer->render(
            $cacheItem->getObject(),
            $cacheItem->getId(),
            $webspaceKey,
            $cacheItem->getLocale(),
            false,
            $targetGroupId
        );

        $cacheItem->setHtml($this->removeContent($html));
        $this->save($cacheItem);

        return $this->renderPartial($cacheItem, $webspaceKey, $targetGroupId);
    }

    public function render(string $token, string $webspaceKey, string $locale, ?int $targetGroupId): string
    {
        $cacheItem = $this->fetch($token);

        $html = $this->renderer->render(
            $cacheItem->getObject(),
            $cacheItem->getId(),
            $webspaceKey,
            $cacheItem->getLocale(),
            false,
            $targetGroupId
        );

        $cacheItem->setHtml($this->removeContent($html));
        $this->save($cacheItem);

        return $this->renderPartial($cacheItem, $webspaceKey, $targetGroupId);
    }

    protected function renderPartial(PreviewCacheItem $cacheItem, string $webspaceKey, ?int $targetGroupId): string
    {
        $partialHtml = $this->renderer->render(
            $cacheItem->getObject(),
            $cacheItem->getId(),
            $webspaceKey,
            $cacheItem->getLocale(),
            true,
            $targetGroupId
        );

        return str_replace(self::CONTENT_REPLACER, $partialHtml, $cacheItem->getHtml());
    }

    protected function removeContent(string $html): string
    {
        $parts = explode(self::CONTENT_REPLACER, $html);

        return $parts[0] . self::CONTENT_REPLACER . $parts[2];
    }

    protected function getProvider(string $providerKey): PreviewObjectProviderInterface
    {
        if (!array_key_exists($providerKey, $this->objectProviders)) {
            throw new ProviderNotFoundException($providerKey);
        }

        return $this->objectProviders[$providerKey];
    }

    protected function save(PreviewCacheItem $item): void
    {
        $data = [
            'id' => $item->getId(),
            'locale' => $item->getLocale(),
            'userId' => $item->getUserId(),
            'providerKey' => $item->getProviderKey(),
            'html' => $item->getHtml(),
            'object' => $this->getProvider($item->getProviderKey())->serialize($item->getObject()),
            'objectClass' => get_class($item->getObject()),
        ];

        $this->cache->save($item->getToken(), json_encode($data), $this->cacheLifeTime);
    }

    protected function fetch(string $token): PreviewCacheItem
    {
        if (!$this->exists($token)) {
            throw new TokenNotFoundException($token);
        }

        $data = json_decode($this->cache->fetch($token), true);
        $provider = $this->getProvider($data['providerKey']);

        $cacheItem = new PreviewCacheItem(
            $data['id'],
            $data['locale'],
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
