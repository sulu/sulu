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

class WorkspaceCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkspaceCollection
     */
    private $workspaceCollection;

    public function setUp()
    {
        $this->workspaceCollection = new WorkspaceCollection();

        // first portal
        $portal = new Portal();
        $portal->setName('Portal1');
        $portal->setKey('portal1');

        $theme = new Theme();
        $theme->setKey('portal1theme');
        $theme->setExcludedTemplates(array('overview', 'default'));
        $portal->setTheme($theme);

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

        $workspace = new Workspace();
        $workspace->addLocalization($localizationEnUs);
        $workspace->addLocalization($localizationFrCa);
        $workspace->addPortal($portal);
        $workspace->setKey('default');
        $workspace->setName('Default');
        $workspace->addPortal($portal);

        $this->workspaceCollection->add($workspace);
    }

    public function testAdd()
    {
        $workspacesReflection = new \ReflectionProperty('\Sulu\Component\Workspace\WorkspaceCollection', 'workspaces');
        $workspacesReflection->setAccessible(true);
        $allPortalsReflection = new \ReflectionProperty('\Sulu\Component\Workspace\WorkspaceCollection', 'allPortals');
        $allPortalsReflection->setAccessible(true);
        $environmentPortalsReflection = new \ReflectionProperty('\Sulu\Component\Workspace\WorkspaceCollection', 'environmentPortals');
        $environmentPortalsReflection->setAccessible(true);

        $workspaces = $workspacesReflection->getValue($this->workspaceCollection);
        $allPortals = $allPortalsReflection->getValue($this->workspaceCollection);
        $environmentPortals = $environmentPortalsReflection->getValue($this->workspaceCollection);

        $this->assertEquals('Default', $workspaces['default']->getName());
        $this->assertEquals('Portal1', $allPortals['portal1']->getName());
        // TODO make next two lines possible
        $this->assertEquals('Portal1', $environmentPortals['prod']['www.portal1.com']['portal']->getName());
        //$this->assertEquals('Portal1', $environmentPortals['prod']['portal1.com']['portal']->getName());
    }

    public function testToArray()
    {
        $workspace = $this->workspaceCollection->toArray()[0];

        $this->assertEquals('Default', $workspace['name']);
        $this->assertEquals('default', $workspace['key']);
        $this->assertEquals('us', $workspace['localizations'][0]['country']);
        $this->assertEquals('en', $workspace['localizations'][0]['language']);
        $this->assertEquals('ca', $workspace['localizations'][0]['children'][0]['country']);
        $this->assertEquals('en', $workspace['localizations'][0]['children'][0]['language']);
        $this->assertEquals('ca', $workspace['localizations'][1]['country']);
        $this->assertEquals('fr', $workspace['localizations'][1]['language']);

        $portal = $workspace['portals'][0];

        $this->assertEquals('Portal1', $portal['name']);
        $this->assertEquals('portal1theme', $portal['theme']['key']);
        $this->assertEquals(array('overview', 'default'), $portal['theme']['excludedTemplates']);
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
