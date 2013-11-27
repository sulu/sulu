<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace;

class PortalCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PortalCollection
     */
    private $portalCollection;

    public function setUp()
    {
        $this->portalCollection = new PortalCollection();

        // first portal
        $portal = new Portal();
        $portal->setName('Portal1');

        $theme = new Theme();
        $theme->setKey('portal1theme');
        $theme->setExcludedTemplates(array('overview', 'default'));
        $portal->setTheme($theme);

        $environment = new Environment();
        $environment->setType('prod');
        $url = new Url();
        $url->setMain(true);
        $url->setUrl('www.portal1.com');
        $environment->addUrl($url);
        $url = new Url();
        $url->setMain(false);
        $url->setUrl('portal1.com');
        $environment->addUrl($url);
        $portal->addEnvironment($environment);

        $localizationEnUs = new Localization();
        $localizationEnUs->setCountry('us');
        $localizationEnUs->setLanguage('en');
        $localizationEnUs->setDefault(true);
        $localizationEnUs->setShadow('auto');
        $localizationEnCa = new Localization();
        $localizationEnCa->setCountry('ca');
        $localizationEnCa->setLanguage('en');
        $localizationEnCa->setDefault(false);
        $localizationEnUs->addChild($localizationEnCa);
        $localizationFrCa = new Localization();
        $localizationFrCa->setCountry('ca');
        $localizationFrCa->setLanguage('fr');
        $localizationFrCa->setDefault(false);
        $portal->addLocalization($localizationEnUs);
        $portal->addLocalization($localizationEnCa);
        $portal->addLocalization($localizationFrCa);

        $portal->setResourceLocatorStrategy('tree');

        $workspace = new Workspace();
        $workspace->addLocalization($localizationEnUs);
        $workspace->addLocalization($localizationFrCa);
        $workspace->addPortal($portal);
        $workspace->setKey('default');
        $workspace->setName('Default');
        $portal->setWorkspace($workspace);

        $this->portalCollection->add($portal);
    }

    public function testToArray()
    {
        $portal = $this->portalCollection->toArray()[0];

        $this->assertEquals('Portal1', $portal['name']);
        $this->assertEquals('portal1theme', $portal['theme']['key']);
        $this->assertEquals(array('overview', 'default'), $portal['theme']['excludedTemplates']);
        $this->assertEquals('prod', $portal['environments'][0]['type']);
        $this->assertEquals(true, $portal['environments'][0]['urls'][0]['main']);
        $this->assertEquals('www.portal1.com', $portal['environments'][0]['urls'][0]['url']);
        $this->assertEquals(false, $portal['environments'][0]['urls'][1]['main']);
        $this->assertEquals('portal1.com', $portal['environments'][0]['urls'][1]['url']);
        $this->assertEquals('us', $portal['localizations'][0]['country']);
        $this->assertEquals('en', $portal['localizations'][0]['language']);
        $this->assertEquals('ca', $portal['localizations'][1]['country']);
        $this->assertEquals('en', $portal['localizations'][1]['language']);
        $this->assertEquals('ca', $portal['localizations'][2]['country']);
        $this->assertEquals('fr', $portal['localizations'][2]['language']);
        $this->assertEquals('tree', $portal['resourceLocator']['strategy']);
        $this->assertEquals('default', $portal['workspace']['key']);
        $this->assertEquals('us', $portal['workspace']['localizations'][0]['country']);
        $this->assertEquals('en', $portal['workspace']['localizations'][0]['language']);
        $this->assertEquals('ca', $portal['workspace']['localizations'][0]['children'][0]['country']);
        $this->assertEquals('en', $portal['workspace']['localizations'][0]['children'][0]['language']);
        $this->assertEquals('ca', $portal['workspace']['localizations'][1]['country']);
        $this->assertEquals('fr', $portal['workspace']['localizations'][1]['language']);
    }
}
