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
        $progressBar->display();

        foreach ($query->iterate() as $item) {
            $entity = $item[0];

            if (null !== $entity->getRoute()) {
                $routeManager->update($entity);
            } else {
                $entityManager->persist($routeManager->create($entity));
            }

            $entityManager->flush();
            $progressBar->advance();

            // garbage collect this entity
            $entityManager->detach($entity);
        }

        //$progressBar->finish();
        $output->writeln('');
    }
}
