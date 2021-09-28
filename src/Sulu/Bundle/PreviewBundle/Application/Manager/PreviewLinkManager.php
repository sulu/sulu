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

use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;

class PreviewLinkManager implements PreviewLinkManagerInterface
{
    /**
     * @var PreviewLinkRepositoryInterface
     */
    private $previewLinkRepository;

    public function __construct(PreviewLinkRepositoryInterface $previewLinkRepository)
    {
        $this->previewLinkRepository = $previewLinkRepository;
    }

    public function generate(
        string $resourceKey,
        string $resourceId,
        string $locale,
        array $options
    ): PreviewLinkInterface {
        $previewLink = $this->previewLinkRepository->createNew($resourceKey, $resourceId, $locale, $options);
        $this->previewLinkRepository->add($previewLink);
        $this->previewLinkRepository->commit();

        return $previewLink;
    }

    public function revoke(string $resourceKey, string $resourceId, string $locale): void
    {
        $previewLink = $this->previewLinkRepository->findByResource($resourceKey, $resourceId, $locale);
        if (!$previewLink) {
            return;
        }

        $this->previewLinkRepository->remove($previewLink);
        $this->previewLinkRepository->commit();
    }
}
