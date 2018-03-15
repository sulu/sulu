<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Cache;

use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCacheBundle\CacheManager as FOSCacheManager;
use Ramsey\Uuid\Uuid;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sulu cache manager wraps the FOSCacheManager to check if a host replacer is in the path
 * and also checks for every operation if the current proxy client supports the method otherwise just return.
 */
class CacheManager implements CacheManagerInterface
{
    /**
     * @var FOSCacheManager
     */
    private $cacheManager;

    /**
     * @var ReplacerInterface
     */
    private $replacer;

    /**
     * @var null|string
     */
    private $requestHost;

    public function __construct(
        FOSCacheManager $cacheManager,
        RequestStack $requestStack,
        ReplacerInterface $replacer
    ) {
        $this->cacheManager = $cacheManager;
        $this->replacer = $replacer;
        $this->requestHost =
            $requestStack->getCurrentRequest() ? $requestStack->getCurrentRequest()->getHttpHost() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidatePath(string $path, array $headers = []): void
    {
        if (!$this->cacheManager->supports(FOSCacheManager::PATH)) {
            return;
        }

        // replacer host replacer if available
        if ($this->replacer->hasHostReplacer($path)) {
            if (!$this->requestHost) {
                return;
            }

            $path = $this->replacer->replaceHost($path, $this->requestHost);
        }

        $this->cacheManager->invalidatePath($path, $headers);

        if ($this->cacheManager->supports(FOSCacheManager::REFRESH)) {
            $this->cacheManager->refreshPath($path, $headers);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateReference(string $alias, string $id): void
    {
        if (!Uuid::isValid($id)) {
            $id = sprintf('%s-%s', $alias, $id);
        }

        $this->invalidateTag($id);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTag(string $tag): void
    {
        if (!$this->cacheManager->supports(FOSCacheManager::TAGS)) {
            return;
        }

        $this->cacheManager->invalidateTags([$tag]);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateDomain(string $domain): void
    {
        if (!$this->cacheManager->supports(FOSCacheManager::INVALIDATE)) {
            return;
        }

        $this->cacheManager->invalidateRegex(
            BanCapable::REGEX_MATCH_ALL,
            BanCapable::CONTENT_TYPE_ALL,
            $domain
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsInvalidate(): bool
    {
        return $this->cacheManager->supports(FOSCacheManager::INVALIDATE);
    }
}
