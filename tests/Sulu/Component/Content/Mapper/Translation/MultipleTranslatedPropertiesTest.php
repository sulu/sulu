<?php

namespace Sulu\Component\Content\Mapper\Translation;

use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Structure;

class MultipleTranslatedPropertiesTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->properties = new MultipleTranslatedProperties(
            array(
                'template', 'foobar',
            ),
            'i18n'
        );
    }

    public function provideGetName()
    {
        return array(
            array(Structure::TYPE_PAGE, 'foobar', 'i18n:de-foobar'),
            array(Structure::TYPE_SNIPPET, 'template', 'template'),
            array(Structure::TYPE_PAGE, 'template', 'i18n:de-template'),
        );
    }

    /**
     * @dataProvider provideGetName
     */
    public function testGetName($structureType, $name, $expectedName)
    {
        $this->properties->setLanguage('de');
        $this->properties->setStructureType($structureType);
        $res = $this->properties->getName($name);
        $this->assertEquals($expectedName, $res);
    }
}
