<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\Content\Document\WorkflowStage;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional\BaseTestCase;
use Symfony\Component\Security\Core\Tests\Authentication\Token\TestUser;

/**
 * Base test case for functional tests in Sulu.
 */
abstract class SuluTestCase extends BaseTestCase
{
    protected static $kernels = [];
    protected static $currentKernel = 'admin';

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        // enables garbage collector because symfony/phpunit-bridge disables it. see:
        //  * https://github.com/symfony/symfony/pull/13398/files#diff-81bfee6017752d99d3119f4ddb1a09edR1
        //  * https://github.com/symfony/symfony/pull/13398 (feature list)
        if (!gc_enabled()) {
            gc_enable();
        }
    }

    /**
     * Create a new SuluTestKernel and pass the sulu.context to it.
     *
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException If the found kernel does
     *                                  not extend SuluTestKernel
     */
    protected static function createKernel(array $options = [])
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        $kernel = new static::$class(
            isset($options['environment']) ? $options['environment'] : 'test',
            isset($options['debug']) ? $options['debug'] : true,
            isset($options['sulu_context']) ? $options['sulu_context'] : 'admin'
        );

        if (!$kernel instanceof SuluTestKernel) {
            throw new \InvalidArgumentException(sprintf(
                'All Sulu testing Kernel classes must extend SuluTestKernel, "%s" does not',
                get_class($kernel)
            ));
        }

        return $kernel;
    }

    /**
     * Close the database connection after the tests finish.
     */
    public function tearDown()
    {
        $this->db('ORM')->getOm()->getConnection()->close();
    }

    /**
     * Return the test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     *
     * @return TestUser
     */
    protected function getTestUser()
    {
        $user = $this->em->getRepository('Sulu\Bundle\SecurityBundle\Entity\User')
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
        return $this->createClient([
            'sulu_context' => 'website',
            'environment' => 'dev',
        ]);
    }

    /**
     * Initialize / reset the Sulu PHPCR environment.
     *
     * NOTE: We could use the document initializer here rathan manually creating
     *       the webspace nodes, but it currently adds more overhead and offers
     *       no control over *which* webspaces are created, see
     *       https://github.com/sulu-io/sulu/pull/2063 for a solution.
     */
    protected function initPhpcr()
    {
        /** @var SessionInterface $session */
        $session = $this->db('PHPCR')->getOm()->getPhpcrSession();

        if ($session->nodeExists('/cmf')) {
            $session->getNode('/cmf')->remove();
        }

        $this->createHomeDocument('sulu_io', ['de', 'de_at', 'en', 'en_us', 'fr']);
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
        /** @var EntityManager $em */
        $em = $this->db('ORM')->getOm();
        $connection = $em->getConnection();

        if ($connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $connection->executeUpdate('SET foreign_key_checks = 0;');
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($em, $purger);
        $referenceRepository = new ProxyReferenceRepository($em);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        if ($connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $em->getConnection()->executeUpdate('SET foreign_key_checks = 1;');
        }
    }

    /**
     * Shuts the kernel down if it was used in the test.
     */
    protected static function ensureKernelShutdown()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
    }
}
