<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides functionality to handle categories in twig templates.
 */
class CategoryTwigExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_categories', [CategoryRuntime::class, 'getCategoriesFunction']),
            new TwigFunction('sulu_category_url', [CategoryRuntime::class, 'setCategoryUrlFunction']),
            new TwigFunction('sulu_category_url_append', [CategoryRuntime::class, 'appendCategoryUrlFunction']),
            new TwigFunction('sulu_category_url_clear', [CategoryRuntime::class, 'clearCategoryUrlFunction']),
        ];
    }
}
