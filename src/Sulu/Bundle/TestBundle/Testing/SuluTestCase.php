<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use PHPCR\ImportUUIDBehaviorInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Component\Content\Document\WorkflowStage;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Tests\Authentication\Token\TestUser;

/**
 * Base test case for functional tests in Sulu.
 */
abstract class SuluTestCase extends KernelTestCase
{
    private static $workspaceInitialized = false;

    /**
     * @var PHPCRImporter
     */
    protected $importer;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        // enables garbage collector because symfony/phpunit-bridge disables it. see:
        // see: https://github.com/symfony/symfony/pull/13398/files#diff-81bfee6017752d99d3119f4ddb1a09edR1
        // see: https://github.com/symfony/symfony/pull/13398 (feature list)
        if (!gc_enabled()) {
            gc_enable();
        }
    }

    protected function setUp()
    {
        parent::setUp();

        $this->importer = new PHPCRImporter(
            $this->getPhpcrDefaultSession(),
            $this->getPhpcrLiveSession()
        );
    }

    /**
     * Close the database connection after the tests finish.
     */
    public function tearDown()
    {
        parent::tearDown();
        // close the doctrine connection
        foreach ($this->getContainer()->get('doctrine')->getConnections() as $connection) {
            $connection->close();
        }

        // close the jackalope connections - can be removed when
        // https://github.com/sulu/sulu/pull/2125 is merged.
        // this has a negligble impact on memory usage in anycase.
        foreach ($this->getContainer()->get('doctrine_phpcr')->getConnections() as $connection) {
            try {
                $connection->logout();
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Return the test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     *
     * @return TestUser
     */
    protected function getTestUser()
    {
        $user = $this->getEntityManager()->getRepository('Sulu\Bundle\SecurityBundle\Entity\User')
            ->findOneByUsername('test');

        return $user;
    }

    /**
     * Return the ID of the test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     *
     * @return int
     */
    protected function getTestUserId()
    {
        return $this->getTestUser()->getId();
    }

    /**
     * Create an authenticated client.
     *
     * @return Client
     */
    protected function createAuthenticatedClient()
    {
        return $this->createClient(
            [
                'environment' => 'dev',
            ],
            [
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            ]
        );
    }

    /**
     * Create client for tests on the "website" context.
     *
     * @return Client
     */
    protected function createWebsiteClient()
    {
        return $this->createClient(
            [
                'sulu_context' => 'website',
                'environment' => 'dev',
            ]
        );
    }

    /**
     * Initialize / reset Sulu PHPCR environment for given session.
     *
     * @param SessionInterface $session
     * @param string $workspace
     */
    private function importSession(SessionInterface $session, $workspace)
    {
        $initializerDump = $this->getInitializerDumpFilePath($workspace);

        if ($session->nodeExists('/cmf')) {
            NodeHelper::purgeWorkspace($session);
            $session->save();
        }

        $session->importXml('/', $initializerDump, ImportUUIDBehaviorInterface::IMPORT_UUID_COLLISION_THROW);
        $session->save();
    }

    /**
     * Initialize / reset the Sulu PHPCR environment.
     *
     * NOTE: We could use the document initializer here rather than manually creating
     *       the webspace nodes, but it currently adds more overhead and offers
     *       no control over *which* webspaces are created, see
     *       https://github.com/sulu-io/sulu/pull/2063 for a solution.
     */
    protected function initPhpcr()
    {
        $session = $this->getPhpcrDefaultSession();
        $liveSession = $this->getPhpcrLiveSession();

        if (!self::$workspaceInitialized) {
            $this->getInitializer()->initialize(null, true);
            $this->dumpPhpcr($session, 'default');
            $this->dumpPhpcr($liveSession, 'live');
            self::$workspaceInitialized = true;

            return;
        }

        if (!$this->importer) {
            $this->importer = new PHPCRImporter($session, $liveSession);
        }

        $this->importSession($session, 'default');
        if ($session->getWorkspace()->getName() !== $liveSession->getWorkspace()->getName()) {
            $this->importSession($liveSession, 'live');
        }
    }

    /**
     * @param SessionInterface $session
     * @param string $workspace
     */
    protected function dumpPhpcr(SessionInterface $session, $workspace)
    {
        $initializerDump = $this->getInitializerDumpFilePath($workspace);

        $filesystem = new Filesystem();
        if (!$filesystem->exists(dirname($initializerDump))) {
            $filesystem->mkdir(dirname($initializerDump));
        }

        $handle = fopen($initializerDump, 'w');
        $session->exportSystemView('/cmf', $handle, false, false);
        fclose($handle);
    }

    /**
     * @param string $workspace
     *
     * @return null|string
     *
     * @throws \Exception
     */
    protected function getInitializerDumpFilePath($workspace)
    {
        $initializerDump = null;

        switch ($workspace) {
            case 'live':
                $initializerDump = __DIR__ . '/../Resources/app/cache/initial_live.xml';
                break;
            case 'default':
                $initializerDump = __DIR__ . '/../Resources/app/cache/initial.xml';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Workspace "%s" is not a valid option', $workspace));
        }

        return $initializerDump;
    }

    /**
     * Create a webspace node with the given locales.
     *
     * @param string $name
     * @param string[] $locales
     */
    protected function createHomeDocument($name, array $locales)
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $nodeManager = $this->getContainer()->get('sulu_document_manager.node_manager');

        $homeDocument = new HomeDocument();
        $homeDocument->setTitle('Homepage');
        $homeDocument->setStructureType('default');
        $homeDocument->setWorkflowStage(WorkflowStage::PUBLISHED);

        foreach ($locales as $locale) {
            $nodeManager->createPath('/cmf/' . $name . '/routes/' . $locale);
            $documentManager->persist(
                $homeDocument,
                $locale,
                [
                    'path' => '/cmf/' . $name . '/contents',
                    'auto_create' => true,
                    'load_ghost_content' => false,
                ]
            );
        }

        $documentManager->flush();
    }

    /**
     * Purge the Doctrine ORM database.
     */
    protected function purgeDatabase()
    {
        $manager = $this->getEntityManager();
        $connection = $manager->getConnection();

        if ($connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $connection->executeUpdate('SET foreign_key_checks = 0;');
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($manager, $purger);
        $referenceRepository = new ProxyReferenceRepository($manager);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        if ($connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $connection->executeUpdate('SET foreign_key_checks = 1;');
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return Initializer
     */
    protected function getInitializer()
    {
        return $this->getContainer()->get('sulu_document_manager.initializer');
    }

    /**
     * @return SessionInterface
     */
    protected function getPhpcrDefaultSession()
    {
        return $this->getContainer()->get('doctrine_phpcr.session');
    }

    /**
     * @return SessionInterface
     */
    protected function getPhpcrLiveSession()
    {
        return $this->getContainer()->get('doctrine_phpcr.live_session');
    }
}
