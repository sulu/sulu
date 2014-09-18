<?php

namespace Sulu\Component\Localization;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Component\Webspace\Localization;

class LocalizationTest extends ProphecyTestCase
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
        $expected = array(
            'language' => 'fr',
            'localization' => 'fr_at',
            'country' => 'at',
            'default' => 'gb',
            'children' => array(),
            'shadow' => null,
        );

        $this->assertEquals($expected, $this->localization->toArray());
    }
}
