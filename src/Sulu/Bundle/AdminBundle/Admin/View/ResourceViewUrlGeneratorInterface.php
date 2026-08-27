<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates urls to the Sulu Admin frontend application for a given resource,
 * resolving the target view via the `sulu_admin.resources` configuration
 * (`sulu_admin.resources.<resourceKey>.views.<resourceView>`).
 */
interface ResourceViewUrlGeneratorInterface
{
    /**
     * @param array<string, int|string> $viewParameters
     * @param UrlGeneratorInterface::ABSOLUTE_URL|UrlGeneratorInterface::ABSOLUTE_PATH|UrlGeneratorInterface::RELATIVE_PATH|UrlGeneratorInterface::NETWORK_PATH $referenceType
     *
     * @throws ResourceViewNotFoundException
     * @throws ViewNotFoundException
     * @throws ViewParameterNotFoundException
     */
    public function generate(
        string $resourceKey,
        string $resourceView,
        array $viewParameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string;
}
