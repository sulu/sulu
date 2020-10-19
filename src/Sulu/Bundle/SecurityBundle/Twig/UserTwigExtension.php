<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to handle users in frontend.
 */
class UserTwigExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_resolve_user', [UserRuntime::class, 'resolveUserFunction']),
        ];
    }
}
