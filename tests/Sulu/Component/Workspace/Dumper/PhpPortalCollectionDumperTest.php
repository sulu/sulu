<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\Dumper;


use Sulu\Component\Workspace\Environment;
use Sulu\Component\Workspace\Localization;
use Sulu\Component\Workspace\Portal;
use Sulu\Component\Workspace\PortalCollection;
use Sulu\Component\Workspace\Theme;
use Sulu\Component\Workspace\Url;
use Sulu\Component\Workspace\Workspace;

class PhpPortalCollectionDumperTest extends \PHPUnit_Framework_TestCase
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
        $portal->setKey('portal1');

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

    public function testDump()
    {
        $dumper = new PhpPortalCollectionDumper($this->portalCollection);
    }
}
