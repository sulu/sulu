<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Dumper;


use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\Dumper\PhpWebspaceCollectionDumper;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

class PhpWebspaceCollectionDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebspaceCollection
     */
    private $webspaceCollection;

    public function setUp()
    {
        $this->webspaceCollection = new WebspaceCollection();

        $webspace = new Webspace();
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
        $webspace->addLocalization($localizationEnUs);
        $webspace->addLocalization($localizationFrCa);
        $webspace->setKey('default');
        $webspace->setName('Default');

        // first portal
        $portal = new Portal();
        $portal->setName('Portal1');
        $portal->setKey('portal1');

        $theme = new Theme();
        $theme->setKey('portal1theme');
        $theme->setExcludedTemplates(array('overview', 'default'));
        $webspace->setTheme($theme);

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

        $webspace->addPortal($portal);

        $this->webspaceCollection->setWebspaces(array($webspace));
        $this->webspaceCollection->setPortals(array($portal));
    }

    public function testDump()
    {
        $dumper = new PhpWebspaceCollectionDumper($this->webspaceCollection);

        $dump = $dumper->dump(array('cache_class' => 'WebspaceCollectionCache', 'base_class' => 'WebspaceCollection'));
    }
}
