<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update the routes for all entities which will be returned by the repository of given entity service.
 */
class UpdateRouteCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:route:update')
            ->addArgument('entity', InputArgument::REQUIRED)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, '', 1000)
            ->setDescription('Update the routes for all entities.')
            ->setHelp(
                <<<'EOT'
Update the routes for all entities which will be returned by the repository of given entity service:

    $ %command.full_name% sulu.repository.example
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $batchSize = $input->getOption('batch-size');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $routeManager = $this->getContainer()->get('sulu_route.manager.route_manager');
        /** @var EntityRepository $repository */
        $repository = $entityManager->getRepository($input->getArgument('entity'));

        $query = $repository->createQueryBuilder('entity')->select('count(entity.id)')->getQuery();
        $result = $query->getResult();
        $count = (int) $result[0][1];

        $query = $repository->createQueryBuilder('entity')->getQuery();
        $output->writeln(
            sprintf(
                '<comment>updating route for "%s" instances of "%s"</comment>',
                $count,
                $input->getArgument('entity')
            )
        );
        $progressBar = new ProgressBar($output, $count);
        $progressBar->setFormat('debug');
        $progressBar->display();

        $index = 0;
        foreach ($query->iterate() as $item) {
            $entity = $item[0];

            if (null !== $entity->getRoute()) {
                $routeManager->update($entity);
            } else {
                $entityManager->persist($routeManager->create($entity));
            }

            $progressBar->advance();
            $entity = null;

            if (0 === ($index++ % $batchSize)) {
                $entityManager->flush();

                // trigger garbage collect
                $entityManager->clear();
            }
        }

        // flush the rest of the entities
        $entityManager->flush();

        //$progressBar->finish();
        $output->writeln('');
    }
}
