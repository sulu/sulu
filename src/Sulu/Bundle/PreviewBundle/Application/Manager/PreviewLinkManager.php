<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Application\Manager;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\PreviewBundle\Domain\Event\PreviewLinkGeneratedEvent;
use Sulu\Bundle\PreviewBundle\Domain\Event\PreviewLinkRevokedEvent;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Symfony\Component\Routing\RouterInterface;

class PreviewLinkManager implements PreviewLinkManagerInterface
{
    public function __construct(
        private PreviewLinkRepositoryInterface $previewLinkRepository,
        private DomainEventCollectorInterface $domainEventCollector,
        private PreviewObjectProviderRegistryInterface $previewObjectProviderRegistry,
        private RouterInterface $router,
    ) {
    }

    public function generate(
        string $resourceKey,
        string $resourceId,
        string $locale,
        array $options
    ): PreviewLinkInterface {
        $previewLink = $this->previewLinkRepository->create($resourceKey, $resourceId, $locale, $options);
        $this->previewLinkRepository->add($previewLink);
        $this->domainEventCollector->collect(
            new PreviewLinkGeneratedEvent(
                $previewLink,
                $this->router->generate(
                    'sulu_preview.public_render',
                    ['token' => $previewLink->getToken()],
                    RouterInterface::ABSOLUTE_URL
                ),
                [
                    'resourceKey' => $resourceKey,
                    'resourceId' => $resourceId,
                    'locale' => $locale,
                    'options' => $options,
                ],
                $this->resolveSecurityContext($resourceKey, $resourceId, $locale)
            )
        );
        $this->previewLinkRepository->commit();

        return $previewLink;
    }

    public function revoke(string $resourceKey, string $resourceId, string $locale): void
    {
        $previewLink = $this->previewLinkRepository->findByResource($resourceKey, $resourceId, $locale);
        if (!$previewLink) {
            return;
        }

        $link = $this->router->generate(
            'sulu_preview.public_render',
            ['token' => $previewLink->getToken()],
            RouterInterface::ABSOLUTE_URL
        );

        $this->previewLinkRepository->remove($previewLink);
        $this->domainEventCollector->collect(
            new PreviewLinkRevokedEvent(
                $resourceKey,
                $resourceId,
                $link,
                $this->resolveSecurityContext($resourceKey, $resourceId, $locale)
            )
        );
        $this->previewLinkRepository->commit();
    }

    protected function resolveSecurityContext(string $resourceKey, string $resourceId, string $locale): ?string
    {
        $provider = $this->previewObjectProviderRegistry->getPreviewObjectProvider($resourceKey);

        return $provider->getSecurityContext(new PreviewContext($resourceId, $locale));
    }
}
