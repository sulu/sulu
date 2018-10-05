<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Admin\Helper;

class SystemLanguageSelect
{
    /**
     * @var array
     */
    private $translatedLocales;

    public function __construct(array $translatedLocales)
    {
        $this->translatedLocales = $translatedLocales;
    }

    public function getValues(): array
    {
        $values = [];
        foreach ($this->translatedLocales as $value => $title) {
            $values[] = [
                'value' => $value,
                'title' => $title,
            ];
        }

        return $values;
    }
}
