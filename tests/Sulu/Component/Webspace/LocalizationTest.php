<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization;

class LocalizationTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->localization = new Localization();
        $this->localization->setLanguage('fr');
        $this->localization->setCountry('at');
        $this->localization->setDefault('gb');
    }

    public function testToArray()
    {
        $expected = [
            'language' => 'fr',
            'localization' => 'fr_at',
            'country' => 'at',
            'default' => 'gb',
            'children' => [],
            'shadow' => null,
        ];

        $this->assertEquals($expected, $this->localization->toArray());
    }
}
