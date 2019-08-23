<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Core;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension providing generally useful utilities which are available
 * in the Sulu\Component\Util namespace.
 */
class UtilTwigExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('sulu_util_multisort', 'Sulu\Component\Util\SortUtils::multisort'),
            new TwigFilter('sulu_util_filter', 'Sulu\Component\Util\ArrayUtils::filter'),
            new TwigFilter('sulu_util_domain_info', 'tld_extract'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_util_domain_info', 'tld_extract'),
        ];
    }
}
