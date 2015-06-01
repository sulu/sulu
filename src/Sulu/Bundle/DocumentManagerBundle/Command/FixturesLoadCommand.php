<?php

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureLoader;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentExecutor;
use Symfony\Component\HttpKernel\KernelInterface;

class FixturesLoadCommand extends Command
{
    /**
     * @var DocumentFixtureLoader
     */
    private $loader;

    /**
     * @var DocumentExecutor
     */
    private $executor;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(
        DocumentFixtureLoader $loader,
        DocumentExecutor $executor,
        KernelInterface $kernel
    ) {
        parent::__construct();
        $this->loader = $loader;
        $this->executor = $executor;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('sulu:document:fixtures:load')
            ->setDescription('Load Sulu document fixtures')
            ->addOption('fixtures', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'The directory or file to load data fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures to the existing data - will not purge the workspace.')
            ->addOption('no-initialize', null, InputOption::VALUE_NONE, 'Do not run the repository initializers after purging the repository.')
            ->setHelp(<<<EOT
The <info>sulu:document:fixtures:load</info> command loads data fixtures from
your bundles DataFixtures/Document directory:

  <info>%command.full_name%</info>

You can also optionally specify the path to fixtures with the
<info>--fixtures</info> option:

  <info>%command.full_name% --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

If you want to append the fixtures instead of flushing the database first you
can use the <info>--append</info> option:

  <info>%command.full_name% --append</info>

This command will also execute any registered Initializer classes after
purging.
EOT
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $noInitialize = $input->getOption('no-initialize');
        $append = $input->getOption('append');

        if ($input->isInteractive() && !$append) {
            $dialog = $this->getHelperSet()->get('dialog');
            $confirmed = $dialog->askConfirmation(
                $output, 
                '<question>Careful, database will be purged. Do you want to continue Y/N ?</question>', 
                false
            );

            if (!$confirmed) {
                return 0;
            }
        }

        $paths = $input->getOption('fixtures');

        $candidatePaths = array();
        if (!$paths) {
            $paths = array();
            foreach ($this->kernel->getBundles() as $bundle) {
                $candidatePath = $bundle->getPath() . '/DataFixtures/Document';
                $candidatePaths[] = $candidatePath;
                if (file_exists($candidatePath)) {
                    $paths[] = $candidatePath;
                }
            }
        }

        if (empty($paths)) {
            $output->writeln(sprintf(
                '<info>Could not find any existing candidate paths. Looked for: "</info>%s<comment>"</comment>',
                implode('<comment>", "</comment>', $candidatePaths)
            ));
            return 0;
        }

        $fixtures = $this->loader->load($paths);
        $this->executor->execute($fixtures, false === $append, false === $noInitialize, $output);
        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Done. Executed </info>%s</info><info> fixtures.</info>',
            count($fixtures)
        ));
    }
}
