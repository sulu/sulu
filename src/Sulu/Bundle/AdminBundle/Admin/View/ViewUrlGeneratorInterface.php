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

use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SymfonyUrlGeneratorInterface;

/**
 * Generates urls to the Sulu Admin frontend application which point to a given View.
 */
interface ViewUrlGeneratorInterface
{
    public const ABSOLUTE_URL = SymfonyUrlGeneratorInterface::ABSOLUTE_URL;

    public const ABSOLUTE_PATH = SymfonyUrlGeneratorInterface::ABSOLUTE_PATH;

    public const RELATIVE_PATH = SymfonyUrlGeneratorInterface::RELATIVE_PATH;

    public const NETWORK_PATH = SymfonyUrlGeneratorInterface::NETWORK_PATH;

    /**
     * @param array<string, int|string> $viewParameters
     *
     * @throws ViewNotFoundException
     * @throws ViewParameterNotFoundException
     */
    public function generate(
        string $viewName,
        array $viewParameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string;
}
