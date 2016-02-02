<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Build;

use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

class TranslationBuilder extends SuluBuilder
{
    /**
     * @var string[]
     */
    private $locales;

    public function __construct($locales)
    {
        $this->locales = $locales;
    }

    public function getName()
    {
        return 'translations';
    }

    public function getDependencies()
    {
        return ['database'];
    }

    public function build()
    {
        foreach ($this->locales as $locale) {
            $this->execCommand('Translations', 'sulu:translate:import', ['locale' => $locale]);
            $this->execCommand('Translations', 'sulu:translate:export', ['locale' => $locale]);
        }
    }
}
