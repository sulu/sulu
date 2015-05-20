<?php

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
