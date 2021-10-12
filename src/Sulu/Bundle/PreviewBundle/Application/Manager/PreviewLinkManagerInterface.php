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

interface PreviewLinkManagerInterface
{
    /**
     * @param mixed[] $options
     */
    public function generate(
        string $resourceKey,
        string $resourceId,
        string $locale,
        array $options
    ): PreviewLinkInterface;

    public function revoke(string $resourceKey, string $resourceId, string $locale): void;
}
