<?php

namespace Sulu\Bundle\TestBundle\Testing;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;

/**
 * Base test case for functional tests in Sulu
 *
 * NOTE: This class deprecates both PhpcrTestCase and DatabaseTestCase
 */
abstract class SuluTestCase extends BaseTestCase
{
    /**
     * Return the ID of the test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime)
     *
     * @return TestUser
     */
    protected function getTestUserId()
    {
        $user = $this->em->getRepository('Sulu\Bundle\TestBundle\Entity\TestUser')
            ->findOneByUsername('test');

        return $user->getId();
    }

    /**
     * Initialize / reset the Sulu PHPCR environment
     */
    protected function initPhpcr()
    {
        $session = $this->db('PHPCR')->getOm()->getPhpcrSession();

        if ($session->nodeExists('/cmf')) {
            $session->getNode('/cmf')->remove();
        }

        $session->save();

        $cmf = $session->getRootNode()->addNode('cmf');

        // we should use the doctrinephpcrbundle repository initializer to do this.
        $webspace = $cmf->addNode('sulu_io');
        $nodes = $webspace->addNode('routes');
        $nodes->addNode('de');
        $nodes->addNode('en');
        $content = $webspace->addNode('contents');
        $content->setProperty('i18n:en-template', 'default');
        $content->setProperty('i18n:en-creator', 1);
        $content->setProperty('i18n:en-created', new \DateTime());
        $content->setProperty('i18n:en-changer', 1);
        $content->setProperty('i18n:en-changed', new \DateTime());
        $content->addMixin('sulu:content');
        $webspace->addNode('temp');
        $cmf->addNode('snippets');

        $session->save();
    }

    /**
     * Purge the Doctrine ORM database
     */
    protected function purgeDatabase()
    {
        $em = $this->db('ORM')->getOm();
        $connection = $em->getConnection();

        if ($connection instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $connection->executeUpdate("SET foreign_key_checks = 0;");
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($em, $purger);
        $referenceRepository = new ProxyReferenceRepository($em);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        if ($connection instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $em->getConnection()->executeUpdate("SET foreign_key_checks = 1;");
        }
    }

    /**
     * Create an authenticated client
     *
     * @return Symfony\Bundle\FrameworkBundle\Client
     */
    protected function createAuthenticatedClient()
    {
        return $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    public function tearDown()
    {
        $this->db('ORM')->getOm()->getConnection()->close();
    }
}
