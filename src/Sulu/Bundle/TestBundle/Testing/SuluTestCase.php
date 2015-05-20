<?php

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\Content\Structure;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional\BaseTestCase;
use Symfony\Component\Security\Core\Tests\Authentication\Token\TestUser;

/**
 * Base test case for functional tests in Sulu.
 *
 * NOTE: This class deprecates both PhpcrTestCase and DatabaseTestCase
 */
abstract class SuluTestCase extends BaseTestCase
{
    protected static $kernels = array();
    protected static $currentKernel = 'admin';

    /**
     * Create a new SuluTestKernel and pass the sulu.context to it.
     *
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException If the found kernel does
     *   not extend SuluTestKernel
     */
    protected static function createKernel(array $options = array())
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
            array(
                'environment' => 'dev',
            ),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    /**
     * Create client for tests on the "website" context.
     *
     * @return Client
     */
    protected function createWebsiteClient()
    {
        return $this->createClient(array(
            'sulu_context' => 'website',
            'environment' => 'dev',
        ));
    }

    /**
     * Initialize / reset the Sulu PHPCR environment
     * NOTE: This should use initializers when we implement that feature.
     */
    protected function initPhpcr()
    {
        /** @var SessionInterface $session */
        $session = $this->db('PHPCR')->getOm()->getPhpcrSession();
        $structureManager = $this->getContainer()->get('sulu.content.structure_manager');

        if ($session->nodeExists('/cmf')) {
            $session->getNode('/cmf')->remove();
        }

        $session->save();

        $cmf = $session->getRootNode()->addNode('cmf');

        $snippetsNode = $cmf->addNode('snippets');
        $snippetStructures = $structureManager->getStructures(Structure::TYPE_SNIPPET);

        foreach ($snippetStructures as $snippetStructure) {
            $snippetsNode->addNode($snippetStructure->getKey());
        }

        // we should use the doctrinephpcrbundle repository initializer to do this.
        $webspace = $cmf->addNode('sulu_io');
        $nodes = $webspace->addNode('routes');
        $nodes->addNode('de');
        $nodes->addNode('de_at');
        $nodes->addNode('en');
        $nodes->addNode('en_us');

        $content = $webspace->addNode('contents');
        $content->setProperty('i18n:en-template', 'default');
        $content->setProperty('i18n:en-creator', 1);
        $content->setProperty('i18n:en-created', new \DateTime());
        $content->setProperty('i18n:en-changer', 1);
        $content->setProperty('i18n:en-changed', new \DateTime());
        $content->addMixin('sulu:content');

        $webspace->addNode('temp');

        $session->save();
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
}
