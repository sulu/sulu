<?php

namespace Sulu\Component\Content\Mapper\LocalizationFinder;

class NullWebspaceFinderTest extends \PHPUnit_Framework_TestCase
{
    private $node;
    private $finder;

    public function setUp()
    {
        parent::setUp();
        $this->node = $this->prophesize('PHPCR\NodeInterface');
        $this->nodeHelper = $this->prophesize('Sulu\Component\Util\SuluNodeHelper');

        $this->finder = new NullWebspaceFinder($this->nodeHelper->reveal());
    }

    public function testSupportsNonNullWebspace()
    {
        $res = $this->finder->supports($this->node->reveal(), 'foobar', 'webspace');
        $this->assertFalse($res);
    }

    public function testSupportsNullWebspace()
    {
        $res = $this->finder->supports($this->node->reveal(), 'foobar', null);
        $this->assertTrue($res);
    }

    public function provideGetAvailableLocalizations()
    {
        return array(
            array(
                array('de', 'fr'),
                'fr',
                'fr',
            ),
            array(
                array('de', 'fr'),
                'it',
                'de',
            ),
            array(
                array(),
                'de',
                'de',
            ),
        );
    }

    /**
     * @dataProvider provideGetAvailableLocalizations
     */
    public function testGetAvailableLocalizations($availableLanguages, $wantedLanguage, $expected)
    {
        $this->nodeHelper->getLanguagesForNode($this->node)->willReturn($availableLanguages);
        $res = $this->finder->getAvailableLocalization($this->node->reveal(), $wantedLanguage, null);

        $this->assertEquals($expected, $res);
    }
}
