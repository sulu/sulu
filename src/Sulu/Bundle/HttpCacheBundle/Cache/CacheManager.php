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
    private $fosCacheManager;

    /**
     * @var ReplacerInterface
     */
    private $replacer;

    /**
     * @var null|string
     */
    private $requestHost;

    public function __construct(
        FOSCacheManager $fosCacheManager,
        RequestStack $requestStack,
        ReplacerInterface $replacer
    ) {
        $this->fosCacheManager = $fosCacheManager;
        $this->replacer = $replacer;
        $this->requestHost =
            $requestStack->getCurrentRequest() ? $requestStack->getCurrentRequest()->getHttpHost() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidatePath(string $path, array $headers = []): void
    {
        if (!$this->fosCacheManager->supports(FOSCacheManager::PATH)) {
            return;
        }

        // replacer host replacer if available
        if ($this->replacer->hasHostReplacer($path)) {
            if (!$this->requestHost) {
                return;
            }

            $path = $this->replacer->replaceHost($path, $this->requestHost);
        }

        $this->fosCacheManager->invalidatePath($path, $headers);

        if ($this->fosCacheManager->supports(FOSCacheManager::REFRESH)) {
            $this->fosCacheManager->refreshPath($path, $headers);
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
        if (!$this->fosCacheManager->supports(FOSCacheManager::TAGS)) {
            return;
        }

        $this->fosCacheManager->invalidateTags([$tag]);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateDomain(string $domain): void
    {
        if (!$this->fosCacheManager->supports(FOSCacheManager::INVALIDATE)) {
            return;
        }

        $this->fosCacheManager->invalidateRegex(
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
        return $this->fosCacheManager->supports(FOSCacheManager::INVALIDATE);
    }
}
