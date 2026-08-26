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

    public function __construct(
        private PreviewObjectProviderRegistryInterface $previewObjectProviderRegistry,
        private CacheItemPoolInterface $cache,
        private PreviewRendererInterface $renderer,
        private int $cacheLifeTime = 3600
    ) {
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

        $object = $this->buildObject($provider, $previewContext, $data, []);

        $cacheItem = new PreviewCacheItem($id, $locale, $userId, $providerKey, $object, $data);
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

        $this->cache->deleteItem($token);
    }

    /**
     * Returns true if such a session exists.
     */
    public function exists(string $token): bool
    {
        return $this->cache->hasItem($token);
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
            $previewContext = new PreviewContext($id, $locale);
            // the form always sends its whole data, so the last payload replaces the previous
            // one rather than being merged into it
            $cacheItem->setData($data);
            $cacheItem->setObject($this->buildObject($provider, $previewContext, $data, $cacheItem->getContext()));

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

        if (!empty($data)) {
            $cacheItem->setData($data);
        }

        if (0 === \count($context)) {
            return $this->renderer->render(
                $this->buildObject($provider, $previewContext, $cacheItem->getData(), $cacheItem->getContext()),
                $cacheItem->getId(),
                false,
                $options
            );
        }

        // the values keep being applied over the new context, so switching template does not
        // throw away what has been typed since the last save
        $cacheItem->setContext($context);
        $object = $this->buildObject($provider, $previewContext, $cacheItem->getData(), $context);

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

    /**
     * Builds the route defaults to render, from the saved state plus everything the form has
     * sent since. A provider returns an empty array when it has nothing to render, e.g. no
     * content for the locale, and then has nothing to apply the values to either.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function buildObject(
        PreviewDefaultsProviderInterface $provider,
        PreviewContext $previewContext,
        array $data,
        array $context
    ): array {
        /** @var array<string, mixed> $object */
        $object = $provider->getDefaults($previewContext);

        if ([] === $object) {
            return $object;
        }

        if ([] !== $data) {
            $object = $provider->updateValues($previewContext, $object, $data);
        }

        if ([] !== $context) {
            $object = $provider->updateContext($previewContext, $object, $context);
        }

        return $object;
    }

    protected function save(PreviewCacheItem $item): void
    {
        // the route defaults hold the object itself, which does not survive a JSON round trip;
        // what is stored is the form payload that produced it, so that fetch() can rebuild it
        $data = [
            'id' => $item->getId(),
            'locale' => $item->getLocale(),
            'userId' => $item->getUserId(),
            'providerKey' => $item->getProviderKey(),
            'html' => $item->getHtml(),
            'data' => $item->getData(),
            'context' => $item->getContext(),
        ];

        $id = $item->getToken();
        $cacheItem = $this->cache->getItem($id);
        $cacheItem->set(\json_encode($data));

        if (0 !== $this->cacheLifeTime) {
            $cacheItem->expiresAfter($this->cacheLifeTime);
        }

        $this->cache->save($cacheItem);
    }

    protected function fetch(string $token): PreviewCacheItem
    {
        if (!$this->exists($token)) {
            throw new TokenNotFoundException($token);
        }

        /** @var string $cachedContent */
        $cachedContent = $this->cache->getItem($token)->get();

        /**
         * @var array{
         *     id: string,
         *     locale: string,
         *     userId: int,
         *     providerKey: string,
         *     html: string|null,
         *     data?: array<string, mixed>,
         *     context?: array<string, mixed>,
         * } $data
         */
        $data = \json_decode($cachedContent, true);
        $provider = $this->getProvider($data['providerKey']);

        $values = $data['data'] ?? [];
        $context = $data['context'] ?? [];

        // replaying them is what makes a plain render(), the one the reload button triggers,
        // show the state being edited instead of the last saved version
        $object = $this->buildObject(
            $provider,
            new PreviewContext($data['id'], $data['locale']),
            $values,
            $context
        );

        $cacheItem = new PreviewCacheItem(
            $data['id'],
            $data['locale'],
            $data['userId'],
            $data['providerKey'],
            $object,
            $values,
            $context,
        );

        if ($data['html']) {
            $cacheItem->setHtml($data['html']);
        }

        return $cacheItem;
    }
}
