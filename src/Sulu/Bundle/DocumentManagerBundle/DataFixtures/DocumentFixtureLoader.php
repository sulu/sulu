<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

/**
 * This class locates and sorts fixture files.
 */
class DocumentFixtureLoader
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DocumentFixtureInterface[]
     */
    private $fixtures;

    public function __construct(
        ContainerInterface $container,
        \Traversable $fixtures = null
    ) {
        $this->container = $container;
        $this->fixtures = $fixtures ? iterator_to_array($fixtures) : [];
    }

    /**
     * Load, instantiate and sort all fixture files found
     * within the given paths.
     *
     * @return DocumentFixtureInterface[]
     */
    public function load(array $paths)
    {
        $finder = new Finder();
        $finder->in($paths);
        $finder->name('*Fixture.php');

        $fixtures = [];

        foreach ($finder as $file) {
            $declaredClasses = get_declared_classes();
            require_once $file;
            $declaredClassesDiff = array_diff(get_declared_classes(), $declaredClasses);
            $fixtureClass = array_pop($declaredClassesDiff);

            if (!$fixtureClass) {
                // check if fixture was loaded over DI and use then that one instead of throwing an error
                foreach ($this->fixtures as $fixture) {
                    $reflectionClass = new \ReflectionClass(get_class($fixture));

                    if (realpath($file->getPathname()) === $reflectionClass->getFileName()) {
                        $fixtures[] = $fixture;

                        continue 2;
                    }
                }

                throw new \InvalidArgumentException(sprintf(
                    'Could not determine class from included file "%s". Class detection will only work once per request.',
                    $file
                ));
            }

            $refl = new \ReflectionClass($fixtureClass);

            if ($refl->isAbstract()) {
                continue;
            }

            if (false === $refl->isSubclassOf(DocumentFixtureInterface::class)) {
                continue;
            }

            $fixture = new $fixtureClass();

            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer($this->container);
            }

            $fixtures[] = $fixture;
        }

        usort($fixtures, function(DocumentFixtureInterface $fixture1, DocumentFixtureInterface $fixture2) {
            return $fixture1->getOrder() > $fixture2->getOrder();
        });

        return $fixtures;
    }
}
