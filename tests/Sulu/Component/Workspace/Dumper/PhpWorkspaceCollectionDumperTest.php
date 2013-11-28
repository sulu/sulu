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
use Sulu\Component\Workspace\WorkspaceCollection;
use Sulu\Component\Workspace\Theme;
use Sulu\Component\Workspace\Url;
use Sulu\Component\Workspace\Workspace;

class PhpWorkspaceCollectionDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkspaceCollection
     */
    private $workspaceCollection;

    public function setUp()
    {
        $this->workspaceCollection = new WorkspaceCollection();

        $workspace = new Workspace();
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
        $workspace->addLocalization($localizationEnUs);
        $workspace->addLocalization($localizationFrCa);
        $workspace->setKey('default');
        $workspace->setName('Default');

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
        $environment->addUrl($url);
        $url = new Url();
        $url->setUrl('portal1.com');
        $environment->addUrl($url);
        $portal->addEnvironment($environment);

        $portal->addLocalization($localizationEnUs);
        $portal->addLocalization($localizationEnCa);
        $portal->addLocalization($localizationFrCa);

        $portal->setResourceLocatorStrategy('tree');

        $workspace->addPortal($portal);

        $this->workspaceCollection->add($workspace);
    }

    public function testDump()
    {
        $dumper = new PhpWorkspaceCollectionDumper($this->workspaceCollection);

        $dump = $dumper->dump(array('cache_class' => 'WorkspaceCollectionCache', 'base_class' => 'WorkspaceCollection'));
    }
}
