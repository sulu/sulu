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

use Psr\Log\LoggerInterface;
use Sulu\Component\Workspace\Loader\XmlFileLoader;
use Sulu\Component\Workspace\Manager\WorkspaceManager;

class WorkspaceManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlFileLoader
     */
    protected $loader;

    /**
     * @var WorkspaceManager
     */
    protected $workspaceManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function setUp()
    {
        $locator = $this->getMock('\Symfony\Component\Config\FileLocatorInterface', array('locate'));
        $locator->expects($this->any())->method('locate')->will($this->returnArgument(0));
        $this->loader = new XmlFileLoader($locator);

        $this->logger = $this->getMock('\Psr\Log\LoggerInterface');

        $this->workspaceManager = new WorkspaceManager(
            $this->loader,
            $this->logger,
            array(
                'cache_dir' => __DIR__ . '/../../../Resources/cache',
                'config_dir' => __DIR__ . '/../../../Resources/DataFixtures/Workspace/valid'
            )
        );
    }

    public function tearDown()
    {
        if (file_exists(__DIR__ . '/../../../Resources/cache/WorkspaceCollectionCache.php')) {
            unlink(__DIR__ . '/../../../Resources/cache/WorkspaceCollectionCache.php');
        }
    }

    public function testGetAll()
    {
        $workspaces = $this->workspaceManager->getWorkspaceCollection();

        $workspace = $workspaces->getWorkspace('massiveart');

        $this->assertEquals('Massive Art', $workspace->getName());
        $this->assertEquals('massiveart', $workspace->getKey());

        $this->assertEquals('en', $workspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $workspace->getLocalizations()[0]->getShadow());

        $this->assertEquals(1, count($workspace->getLocalizations()[0]->getChildren()));
        $this->assertEquals('en', $workspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $workspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[0]->getChildren()[0]->getShadow());

        $this->assertEquals('fr', $workspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $workspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[1]->getShadow());


        $portal = $workspace->getPortals()[0];

        $this->assertEquals('Massive Art US', $portal->getName());
        $this->assertEquals('massiveart_us', $portal->getKey());

        $this->assertEquals('tree', $portal->getResourceLocatorStrategy());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals(null, $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals('massiveart', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());


        $portal = $workspace->getPortals()[1];

        $this->assertEquals('Massive Art CA', $portal->getName());
        $this->assertEquals('massiveart_ca', $portal->getKey());

        $this->assertEquals('tree', $portal->getResourceLocatorStrategy());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(null, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('fr', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals('massiveart', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[0]->getCountry());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[0]->getSegment());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[0]->getRedirect());
        $this->assertEquals('www.massiveart.com', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals('en', $portal->getEnvironments()[0]->getUrls()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getEnvironments()[0]->getUrls()[1]->getCountry());
        $this->assertEquals('s', $portal->getEnvironments()[0]->getUrls()[1]->getSegment());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[1]->getRedirect());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());

    }

    public function testFindWorkspaceByKey()
    {
        $workspace = $this->workspaceManager->findWorkspaceByKey('sulu_io');

        $this->assertEquals('Sulu CMF', $workspace->getName());
        $this->assertEquals('sulu_io', $workspace->getKey());

        $this->assertEquals(2, count($workspace->getLocalizations()));
        $this->assertEquals('en', $workspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $workspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $workspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $workspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $workspace->getLocalizations()[1]->getShadow());

        $portal = $workspace->getPortals()[0];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getRedirect());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    public function testFindPortalByKey()
    {
        $portal = $this->workspaceManager->findPortalByKey('sulucmf_at');

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    public function testFindWorkspaceByNotExistingKey()
    {
        $portal = $this->workspaceManager->findWorkspaceByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindPortalByNotExistingKey()
    {
        $portal = $this->workspaceManager->findPortalByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindPortalInformationByUrl()
    {
        $portalInformation = $this->workspaceManager->findPortalInformationByUrl('sulu.at/test/test/test', 'prod');
        $this->assertEquals('de-at', $portalInformation['localization']->getLocalization());
        $this->assertArrayNotHasKey('segment', $portalInformation);

        /** @var Portal $portal */
        $portal = $portalInformation['portal'];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());

        $portalInformation = $this->workspaceManager->findPortalInformationByUrl('sulu.lo', 'dev');
        $this->assertEquals('de-at', $portalInformation['localization']->getLocalization());
        $this->assertArrayNotHasKey('segment', $portalInformation);

        /** @var Portal $portal */
        $portal = $portalInformation['portal'];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    public function testFindPortalInformationByUrlWithSegment()
    {
        $portalInformation = $this->workspaceManager->findPortalInformationByUrl('en.massiveart.us/w/about-us', 'prod');
        $this->assertEquals('en-us', $portalInformation['localization']->getLocalization());
        $this->assertEquals('winter', $portalInformation['segment']->getName());

        /** @var Portal $portal */
        $portal = $portalInformation['portal'];

        $this->assertEquals('Massive Art US', $portal->getName());
        $this->assertEquals('massiveart_us', $portal->getKey());

        $this->assertEquals('tree', $portal->getResourceLocatorStrategy());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals(null, $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals('massiveart', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    public function testInvalidPart()
    {
        $this->logger = $this->getMockForAbstractClass(
            '\Psr\Log\LoggerInterface',
            array(),
            '',
            true,
            true,
            true,
            array('warning')
        );

        $this->logger->expects($this->once())->method('warning')->will($this->returnValue(null));

        $this->workspaceManager = new WorkspaceManager(
            $this->loader,
            $this->logger,
            array(
                'cache_dir' => __DIR__ . '/../../../Resources/cache',
                'config_dir' => __DIR__ . '/../../../Resources/DataFixtures/Workspace/both'
            )
        );

        $workspaces = $this->workspaceManager->getWorkspaceCollection();

        $this->assertEquals(3, $workspaces->length());

        $workspace = $workspaces->getWorkspace('massiveart');

        $this->assertEquals('Massive Art', $workspace->getName());
        $this->assertEquals('massiveart', $workspace->getKey());

        $workspace = $workspaces->getWorkspace('sulu_io');

        $this->assertEquals('Sulu CMF', $workspace->getName());
        $this->assertEquals('sulu_io', $workspace->getKey());
    }
}
