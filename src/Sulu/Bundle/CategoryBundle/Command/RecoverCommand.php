<?php

namespace Sulu\Bundle\CategoryBundle\Command;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Sulu\Bundle\CategoryBundle\Command
 */
class RecoverCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:categories:recover')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'update tree'
            )
            ->addOption(
                'depth',
                'd',
                InputOption::VALUE_NONE,
                'fix depth'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getEntityManager();
        $force = $input->getOption('force');
        $fixDepth = $input->getOption('depth');

//        $import = $this->getContainer()->get('sulu_contact.import');

        $repo = $this->getCategoryRepository();
        $verify = $repo->verify();
        
        $success = false;
        
        // fix nested tree
        if ($verify !== true) {
            $output->writeln(sprintf('<comment>%s errors were found. Repair with --force</comment>', count($verify)));

            if ($force) {
                $repo->recover();
                $em->flush();
                $success = true;
            }
            
        } else {
            $output->writeln('<info>Your categories are fine. No errors found<info>');
        }

        // fix depths
        if ($fixDepth) {
            $numberWrongDepth = $this->findInitialWrongDepth();
            if ($numberWrongDepth > 0) {
                $output->writeln(sprintf('<comment>%s wrong depths were found.</comment>', $numberWrongDepth));
                
                if ($force) {
                    // update depths
                    $affected = 1;
                    while($affected > 0) {
                        $affected = $this->updateDepths();    
                    }
                }
                
            } else {
                $output->writeln('<info>No wrong depths detected<info>');
            }   
        }
        
        if ($success === true) {
            $output->writeln('<info>Recovery complete<info>');
        }
    }

    /**
     * @return CategoryRepository
     */
    private function getCategoryRepository()
    {
        return $this->getContainer()->get('sulu_category.category_repository');
    }

    /**
     * @return object
     */
    private function findInitialWrongDepth()
    {
        $qb = $this->getCategoryRepository()->createQueryBuilder('c2')
            ->select('count(c2.id) as results')
            ->join('c2.parent', 'c1')
            ->where('(c2.depth - 1) <> c1.depth');
        return $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * @return object
     */
    private function updateDepths()
    {
//        $qb = $this->getCategoryRepository()->createQueryBuilder('c2')
//            ->update()
//            ->join('c2.parent', 'c1')
//            ->set('c2.depth', 'c1.depth + 1')
//            ->where('(c2.depth - 1) <> c1.depth');
//        $result = $qb->getQuery()->execute();
        
        $dql = "UPDATE SuluCategoryBundle:Category c2
                JOIN c2.parent c1
                SET c2.depth = c1.depth + 1
                WHERE c2.depth -1 <> c1.depth";
        $query = $this->getEntityManager()->createQuery($dql);
        $result = $query->execute();
        return $result;
        
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
