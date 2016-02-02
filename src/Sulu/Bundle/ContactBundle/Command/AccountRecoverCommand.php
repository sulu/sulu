<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for recovering nested tree of accounts.
 * This command is fixing wrong left/right and depths (see -d) assignments of the nested tree.
 */
class AccountRecoverCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:contacts:accounts:recover')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force recovery of tree. Without it, an analysis of the tree is performed'
            )
            ->addOption(
                'fix-depth',
                'd',
                InputOption::VALUE_NONE,
                'Analyse depths of nodes and fixes depth gaps as well as depths > 0 without a parent'
            );
    }

    /**
     * Execute command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getEntityManager();
        $force = $input->getOption('force');
        $fixDepth = $input->getOption('fix-depth');

        $repo = $this->getEntityRepository();
        $verify = $repo->verify();

        $success = false;

        // fix nested tree
        if ($verify !== true) {
            $output->writeln(sprintf('<comment>%s errors were found.</comment>', count($verify)));

            if ($force) {
                $repo->recover();
                $em->flush();
                $success = true;
            }
        } else {
            $output->writeln('<info>Your tree is fine. No errors found.<info>');
        }

        // fix depths if -depth is defined
        if ($fixDepth) {

            // fix nodes without parents
            $numberParentLess = $this->findNodesWithoutParents();
            if ($numberParentLess > 0) {
                $output->writeln(
                    sprintf('<comment>%s nodes without parent were found.</comment>', $numberParentLess)
                );
                if ($force) {
                    $this->fixNodesWithoutParents();
                    $success = true;
                    $em->flush();
                }
            } else {
                $output->writeln('<info>No wrong depth gaps detected<info>');
            }

            // fix depth gaps
            $numberWrongDepth = $this->findInitialWrongDepthGap();
            if ($numberWrongDepth > 0) {
                $output->writeln(sprintf('<comment>%s wrong depths were found.</comment>', $numberWrongDepth));
                if ($force) {
                    // update depths
                    $affected = 1;
                    while ($affected > 0) {
                        $affected = $this->fixWrongDepthGap();
                    }
                    $success = true;
                    $em->flush();
                }
            } else {
                $output->writeln('<info>No nodes without parents detected<info>');
            }
        }

        if (!$force) {
            $output->writeln(sprintf('Call this command with <info>--force</info> option to perform recovery.'));
        }

        if ($success === true) {
            $output->writeln('<info>Recovery complete<info>');
        }
    }

    /**
     * Find number of nodes where difference to parents depth > 1.
     *
     * @return int Number of affected rows
     */
    private function findInitialWrongDepthGap()
    {
        // get nodes where difference to parents depth > 1
        $qb = $this->getEntityRepository()->createQueryBuilder('c2')
            ->select('count(c2.id) as results')
            ->join('c2.parent', 'c1')
            ->where('(c2.depth - 1) <> c1.depth');
        $depthGapResult = $qb->getQuery()->getSingleScalarResult();

        return $depthGapResult;
    }

    /**
     * Find number of nodes that have no parent but depth > 0.
     *
     * @return int Number of nodes without a parent
     */
    private function findNodesWithoutParents()
    {
        // get nodes that have no parent but depth > 0
        $qb = $this->getEntityRepository()->createQueryBuilder('c2')
            ->select('count(c2.id)')
            ->leftJoin('c2.parent', 'c1')
            ->where('c2.depth <> 0 AND c2.parent IS NULL');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Fix nodes where difference to parents depth.
     *
     * @return int|bool Number of affected rows
     */
    private function fixWrongDepthGap()
    {
        // FIXME: convert this native query to DQL (once its possible to join within UPDATE statement)
        // fix nodes where difference to parents depth > 1
        $sql = 'UPDATE co_accounts c2
                JOIN co_accounts c1 ON c2.idAccountsParent = c1.id
                SET c2.depth = (c1.depth + 1)
                WHERE ( c2.depth - 1 ) <> c1.depth';

        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        if ($statement->execute()) {
            return $statement->rowCount();
        }

        return false;
    }

    /**
     * Set every node where depth > 0 and has no parents to depth 0.
     */
    private function fixNodesWithoutParents()
    {
        // fix nodes that have no parent but depth > 0
        $qb = $this->getEntityRepository()->createQueryBuilder('c2')
            ->update()
            ->set('c2.depth', 0)
            ->where('c2.parent IS NULL AND depth != 0');

        $qb->getQuery()->execute();
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return EntityRepository
     */
    private function getEntityRepository()
    {
        return $this->getContainer()->get('sulu_contact.account_repository');
    }
}
