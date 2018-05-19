<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests;

use Jackalope\RepositoryFactoryDoctrineDBAL;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPCR\SessionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

class Bootstrap
{
    public static function createContainer()
    {
        $logDir = __DIR__ . '/../data/logs';

        if (!file_exists($logDir)) {
            mkdir($logDir);
        }

        $container = new ContainerBuilder();
        $container->set('doctrine_phpcr.session', self::createSession());
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler($logDir . '/test.log'));

        $dispatcher = new ContainerAwareEventDispatcher($container);
        $container->set('sulu_document_manager.event_dispatcher', $dispatcher);

        $config = [
            'sulu_document_manager.default_locale' => 'en',
            'sulu_document_manager.mapping' => [
                'full' => [
                    'alias' => 'full',
                    'phpcr_type' => 'mix:test',
                    'class' => 'Sulu\Component\DocumentManager\Tests\Functional\Model\FullDocument',
                    'mapping' => [
                        'title' => [
                            'encoding' => 'content_localized',
                        ],
                        'body' => [
                            'encoding' => 'content_localized',
                        ],
                        'status' => [
                            'encoding' => 'system',
                            'property' => 'my_status',
                        ],
                        'reference' => [
                            'encoding' => 'content',
                            'type' => 'reference',
                        ],
                    ],
                ],
                'mapping_5' => [
                    'alias' => 'mapping_5',
                    'phpcr_type' => 'mix:mapping5',
                    'class' => 'Sulu\Component\DocumentManager\Tests\Functional\Model\Mapping5Document',
                    'mapping' => [
                        'one' => [],
                        'two' => [],
                        'three' => [],
                        'four' => [],
                        'five' => [],
                    ],
                ],
                'mapping_10' => [
                    'alias' => 'mapping_10',
                    'phpcr_type' => 'mix:mapping10',
                    'class' => 'Sulu\Component\DocumentManager\Tests\Functional\Model\Mapping10Document',
                    'mapping' => [
                        'one' => [],
                        'two' => [],
                        'three' => [],
                        'four' => [],
                        'five' => [],
                        'six' => [],
                        'seven' => [],
                        'eight' => [],
                        'nine' => [],
                        'ten' => [],
                    ],
                ],
            ],
            'sulu_document_manager.namespace_mapping' => [
                'system' => 'nsys',
                'system_localized' => 'lsys',
                'content' => 'ncon',
                'content_localized' => 'lcon',
            ],
        ];

        foreach ($config as $parameterName => $parameterValue) {
            $container->setParameter($parameterName, $parameterValue);
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../symfony-di'));
        $loader->load('core.xml');
        $loader->load('subscribers.xml');

        foreach (array_keys($container->findTaggedServiceIds('sulu_document_manager.event_subscriber')) as $subscriberId) {
            $def = $container->get($subscriberId);
            $dispatcher->addSubscriberService($subscriberId, get_class($def));
        }

        return $container;
    }

    /**
     * Create a new PHPCR session.
     *
     * @return SessionInterface
     */
    public static function createSession()
    {
        $transportName = getenv('SULU_DM_TRANSPORT') ?: 'jackalope-doctrine-dbal';

        switch ($transportName) {
            case 'jackalope-doctrine-dbal':
                return static::createJackalopeDoctrineDbal();
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown transport "%s"', $transportName
        ));
    }

    public static function createDbalConnection()
    {
        $driver = 'pdo_sqlite'; // pdo_pgsql | pdo_sqlite

        $connection = \Doctrine\DBAL\DriverManager::getConnection([
            'driver' => $driver,
            'host' => 'localhost',
            'user' => 'admin',
            'password' => 'admin',
            'path' => __DIR__ . '/../data/test.sqlite',
        ]);

        return $connection;
    }

    private static function createJackalopeDoctrineDbal()
    {
        $connection = self::createDbalConnection();

        $factory = new RepositoryFactoryDoctrineDBAL();
        $repository = $factory->getRepository(
            ['jackalope.doctrine_dbal_connection' => $connection]
        );

        $credentials = new \PHPCR\SimpleCredentials(null, null);

        $session = $repository->login($credentials, 'default');

        $nodeTypeManager = $session->getWorkspace()->getNodeTypeManager();
        if (!$nodeTypeManager->hasNodeType('mix:test')) {
            $nodeTypeManager->registerNodeTypesCnd(<<<'EOT'
[mix:test] > mix:referenceable mix
[mix:mapping5] > mix:referenceable mix
[mix:mapping10] > mix:referenceable mix
EOT
            , true);
        }

        $namespaceRegistry = $session->getWorkspace()->getNamespaceRegistry();
        $namespaceRegistry->registerNamespace('lsys', 'http://example.com/lsys');
        $namespaceRegistry->registerNamespace('nsys', 'http://example.com/nsys');
        $namespaceRegistry->registerNamespace('lcon', 'http://example.com/lcon');
        $namespaceRegistry->registerNamespace('ncon', 'http://example.com/ncon');

        return $session;
    }
}
