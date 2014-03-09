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

class WebspaceCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebspaceCollection
     */
    private $webspaceCollection;

    public function setUp()
    {
        $this->webspaceCollection = new WebspaceCollection();

        // first portal
        $portal = new Portal();
        $portal->setName('Portal1');
        $portal->setKey('portal1');

        $theme = new Theme();
        $theme->setKey('portal1theme');
        $theme->setExcludedTemplates(array('overview', 'default'));

        $environment = new Environment();
        $environment->setType('prod');
        $url = new Url();
        $url->setUrl('www.portal1.com');
        $url->setLanguage('en');
        $url->setCountry('us');
        $environment->addUrl($url);
        $url = new Url();
        $url->setUrl('portal1.com');
        $url->setRedirect('www.portal1.com');
        $environment->addUrl($url);
        $portal->addEnvironment($environment);

        $localizationEnUs = new Localization();
        $localizationEnUs->setCountry('us');
        $localizationEnUs->setLanguage('en');
        $localizationEnUs->setShadow('auto');
        $localizationEnCa = new Localization();
        $localizationEnCa->setCountry('ca');
        $localizationEnCa->setLanguage('en');
        $localizationEnUs->addChild($localizationEnCa);
        $localizationFrCa = new Localization();
        $localizationFrCa->setCountry('ca');
        $localizationFrCa->setLanguage('fr');
        $portal->addLocalization($localizationEnUs);
        $portal->addLocalization($localizationEnCa);
        $portal->addLocalization($localizationFrCa);

        $portal->setResourceLocatorStrategy('tree');

        $webspace = new Webspace();
        $webspace->addLocalization($localizationEnUs);
        $webspace->addLocalization($localizationFrCa);
        $webspace->setTheme($theme);
        $webspace->addPortal($portal);
        $webspace->setKey('default');
        $webspace->setName('Default');
        $webspace->addPortal($portal);

        $this->webspaceCollection->add($webspace);
    }

    public function testAdd()
    {
        $webspacesReflection = new \ReflectionProperty('\Sulu\Component\Webspace\WebspaceCollection', 'webspaces');
        $webspacesReflection->setAccessible(true);
        $allPortalsReflection = new \ReflectionProperty('\Sulu\Component\Webspace\WebspaceCollection', 'allPortals');
        $allPortalsReflection->setAccessible(true);
        $environmentPortalsReflection = new \ReflectionProperty('\Sulu\Component\Webspace\WebspaceCollection', 'environmentPortals');
        $environmentPortalsReflection->setAccessible(true);

        $webspaces = $webspacesReflection->getValue($this->webspaceCollection);
        $allPortals = $allPortalsReflection->getValue($this->webspaceCollection);
        $environmentPortals = $environmentPortalsReflection->getValue($this->webspaceCollection);

        $this->assertEquals('Default', $webspaces['default']->getName());
        $this->assertEquals('Portal1', $allPortals['portal1']->getName());
        // TODO make next two lines possible
        $this->assertEquals('Portal1', $environmentPortals['prod']['www.portal1.com']['portal']->getName());
        //$this->assertEquals('Portal1', $environmentPortals['prod']['portal1.com']['portal']->getName());
    }

    public function testToArray()
    {
        $webspace = $this->webspaceCollection->toArray()[0];

        $this->assertEquals('Default', $webspace['name']);
        $this->assertEquals('default', $webspace['key']);
        $this->assertEquals('us', $webspace['localizations'][0]['country']);
        $this->assertEquals('en', $webspace['localizations'][0]['language']);
        $this->assertEquals('ca', $webspace['localizations'][0]['children'][0]['country']);
        $this->assertEquals('en', $webspace['localizations'][0]['children'][0]['language']);
        $this->assertEquals('ca', $webspace['localizations'][1]['country']);
        $this->assertEquals('fr', $webspace['localizations'][1]['language']);
        $this->assertEquals('portal1theme', $webspace['theme']['key']);
        $this->assertEquals(array('overview', 'default'), $webspace['theme']['excludedTemplates']);

        $portal = $webspace['portals'][0];

        $this->assertEquals('Portal1', $portal['name']);
        $this->assertEquals('prod', $portal['environments'][0]['type']);
        $this->assertEquals('www.portal1.com', $portal['environments'][0]['urls'][0]['url']);
        $this->assertEquals('en', $portal['environments'][0]['urls'][0]['language']);
        $this->assertEquals('us', $portal['environments'][0]['urls'][0]['country']);
        $this->assertEquals(null, $portal['environments'][0]['urls'][0]['segment']);
        $this->assertEquals(null, $portal['environments'][0]['urls'][0]['redirect']);
        $this->assertEquals('portal1.com', $portal['environments'][0]['urls'][1]['url']);
        $this->assertEquals('www.portal1.com', $portal['environments'][0]['urls'][1]['redirect']);
        $this->assertEquals('us', $portal['localizations'][0]['country']);
        $this->assertEquals('en', $portal['localizations'][0]['language']);
        $this->assertEquals('ca', $portal['localizations'][1]['country']);
        $this->assertEquals('en', $portal['localizations'][1]['language']);
        $this->assertEquals('ca', $portal['localizations'][2]['country']);
        $this->assertEquals('fr', $portal['localizations'][2]['language']);
        $this->assertEquals('tree', $portal['resourceLocator']['strategy']);
    }
}
