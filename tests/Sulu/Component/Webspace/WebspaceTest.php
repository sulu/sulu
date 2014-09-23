<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

class WebspaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Webspace
     */
    private $webspace;

    protected function setUp()
    {
        $this->webspace = new Webspace();
        $this->webspace->setKey('sulu_io');
        $this->webspace->setName('sulu.io');
    }

    private function getLocalization($language, $country = '', $shadow = null)
    {
        $locale = new Localization();
        $locale->setLanguage($language);
        $locale->setCountry($country);
        $locale->setShadow($shadow);

        return $locale;
    }

    public function testFindLocalization()
    {
        $localeDe = $this->getLocalization('de');
        $localeDeAt = $this->getLocalization('de', 'at');
        $localeDeCh = $this->getLocalization('de', 'ch');

        $localeDe->addChild($localeDeAt);
        $localeDe->addChild($localeDeCh);

        $localeEn = $this->getLocalization('en');

        $this->webspace->addLocalization($localeDe);
        $this->webspace->addLocalization($localeEn);

        $result = $this->webspace->getLocalization('de');
        $this->assertEquals('de', $result->getLocalization());

        $result = $this->webspace->getLocalization('de_at');
        $this->assertEquals('de_at', $result->getLocalization());

        $result = $this->webspace->getLocalization('de_ch');
        $this->assertEquals('de_ch', $result->getLocalization());

        $result = $this->webspace->getLocalization('en');
        $this->assertEquals('en', $result->getLocalization());

        $result = $this->webspace->getLocalization('en_us');
        $this->assertEquals(null, $result);
    }

}
