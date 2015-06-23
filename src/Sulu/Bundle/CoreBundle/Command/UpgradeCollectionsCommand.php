<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrades collections to 0.17.0.
 *
 * @deprecated
 */
class UpgradeCollectionsCommand extends ContainerAwareCommand
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:upgrade:0.17.0:collections')->setDescription('Upgrades collections to 0.17.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->doctrine = $this->getContainer()->get('doctrine');
        $repository = $this->doctrine->getRepository('SuluMediaBundle:Collection');

        foreach ($repository->findAll() as $collection) {
            $this->upgradeCollection($collection);
        }

        $this->doctrine->getManager()->flush();
    }

    private function upgradeCollection(Collection $collection)
    {
        $collection->setDefaultMeta($collection->getMeta()->first());
    }
}
