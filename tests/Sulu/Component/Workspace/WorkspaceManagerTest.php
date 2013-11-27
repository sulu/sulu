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
                'config_dir' => __DIR__ . '/../../../Resources/DataFixtures/Portal/valid'
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
        $this->assertEquals(true, $workspace->getLocalizations()[0]->isDefault());

        $this->assertEquals(1, count($workspace->getLocalizations()[0]->getChildren()));
        $this->assertEquals('en', $workspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $workspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[0]->getChildren()[0]->getShadow());
        $this->assertEquals(false, $workspace->getLocalizations()[0]->getChildren()[0]->isDefault());

        $this->assertEquals('fr', $workspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $workspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[1]->getShadow());
        $this->assertEquals(false, $workspace->getLocalizations()[1]->isDefault());

        $portal = $workspace->getPortals()[0];

        $this->assertEquals('Massive Art US', $portal->getName());
        $this->assertEquals('massiveart_us', $portal->getKey());

        $this->assertEquals('tree', $portal->getResourceLocatorStrategy());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals(true, $portal->getLocalizations()[0]->isDefault());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('massiveart', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('massiveart.com', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.massiveart.com', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());

        $portal = $workspaces->get('sulu_io');

        $this->assertEquals('Sulu CMF', $portal->getName());
        $this->assertEquals('sulu_io', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->isMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->isFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->isMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->isFallback());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.io', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.io', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
    }

    public function testFindWorkspaceByKey()
    {
        $workspace = $this->workspaceManager->findWorkspaceByKey('sulu_io');

        $this->assertEquals('Sulu CMF', $workspace->getName());
        $this->assertEquals('sulu_io', $workspace->getKey());

        $this->assertEquals(2, count($workspace->getLocalizations()));
        $this->assertEquals('en', $workspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getLocalizations()[0]->getCountry());
        $this->assertEquals(true, $workspace->getLocalizations()[0]->isDefault());
        $this->assertEquals('auto', $workspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $workspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $workspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $workspace->getLocalizations()[1]->isDefault());
        $this->assertEquals('', $workspace->getLocalizations()[1]->getShadow());

        $portal = $workspace->getPortals()[0];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(true, $portal->getLocalizations()[0]->isDefault());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
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
        $this->assertEquals(true, $portal->getLocalizations()[0]->isDefault());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
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

        /** @var Portal $portal */
        $portal = $portalInformation['portal'];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(true, $portal->getLocalizations()[0]->isDefault());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());

        $portalInformation = $this->workspaceManager->findPortalInformationByUrl('sulu.lo', 'dev');
        $this->assertEquals('de-at', $portalInformation['localization']->getLocalization());

        /** @var Portal $portal */
        $portal = $portalInformation['portal'];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(true, $portal->getLocalizations()[0]->isDefault());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
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
                'config_dir' => __DIR__ . '/../../../Resources/DataFixtures/Portal/both'
            )
        );

        $workspaces = $this->workspaceManager->getWorkspaceCollection();

        $this->assertEquals(2, $workspaces->length());

        $workspace = $workspaces->getWorkspace('massiveart');

        $this->assertEquals('Massive Art', $workspace->getName());
        $this->assertEquals('massiveart', $workspace->getKey());

        $workspace = $workspaces->getWorkspace('sulu_io');

        $this->assertEquals('Sulu CMF', $workspace->getName());
        $this->assertEquals('sulu_io', $workspace->getKey());
    }
}
