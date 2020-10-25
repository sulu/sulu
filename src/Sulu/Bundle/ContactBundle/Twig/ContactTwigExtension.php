<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to handle contacts in frontend.
 */
class ContactTwigExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_resolve_contact', [ContactRuntime::class, 'resolveContactFunction']),
        ];
    }
}
