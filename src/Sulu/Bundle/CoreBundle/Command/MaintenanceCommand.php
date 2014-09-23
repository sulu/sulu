<?php

namespace Sulu\Bundle\CoreBundle\Command;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Component\Maintainence\MaintainenceManager;
use Symfony\Component\Console\Input\InputOption;
use Sulu\Component\Maintenance\MaintenanceManager;
use Symfony\Component\Console\Input\InputArgument;

class MaintenanceCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('sulu:content:maintain');
        $this->setDescription('Maintain the Sulu content repository');
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name of maintainer to run (optional)');
        $this->addOption('list', null, InputOption::VALUE_NONE, 'List maintainers');
        $this->setHelp(<<<EOT
The %command.full_name% command executes maintenance services which assure the
consistency of the Sulu content repository.
EOT
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $maintenanceManager = $this->getContainer()->get('sulu.maintenance_manager');
        $listMaintainers = $input->getOption('list');
        $maintainerName = $input->getArgument('name');

        if (true === $listMaintainers) {
            $this->listMaintainers($output, $maintenanceManager);
            return 0;
        }

        if ($maintainerName) {
            $maintainers = array($maintenanceManager->getMaintainer($maintainerName));
        } else {
            $maintainers = $maintenanceManager->getMaintainers();
        }

        foreach ($maintainers as $maintainer) {
            if ($maintainer instanceof ContainerAwareInterface) {
                $maintainer->setContainer($this->getContainer());
            }

            $maintainer->maintain($output);
        }
    }

    private function listMaintainers(OutputInterface $output, MaintenanceManager $manager)
    {
        $table = new Table($output);
        $table->setHeaders(array('Name'));

        foreach ($manager->getMaintainers() as $maintainer) {
            $table->addRow(array($maintainer->getName()));
        }

        $table->render();
    }
}
