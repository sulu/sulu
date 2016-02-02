<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Tests\Unit;

use Sulu\Component\Localization\Localization;

class LocalizationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Localization
     */
    private $localization;

    public function testToArray()
    {
        $this->localization = new Localization();
        $this->localization->setLanguage('fr');
        $this->localization->setCountry('at');
        $this->localization->setDefault(true);
        $this->localization->setXDefault(true);

        $expected = [
            'language' => 'fr',
            'localization' => 'fr_at',
            'country' => 'at',
            'default' => true,
            'xDefault' => true,
            'children' => [],
            'shadow' => null,
        ];

        $this->assertEquals($expected, $this->localization->toArray());
    }
}
