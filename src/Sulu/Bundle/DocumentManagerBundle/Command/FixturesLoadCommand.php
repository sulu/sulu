<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentExecutor;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FixturesLoadCommand extends Command
{
    protected static $defaultName = 'sulu:document:fixtures:load';

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

    /**
     * @var \Traversable<DocumentFixtureInterface>
     */
    private $fixtures;

    public function __construct(
        DocumentFixtureLoader $loader,
        DocumentExecutor $executor,
        KernelInterface $kernel,
        \Traversable $fixtures = null
    ) {
        parent::__construct();

        $this->loader = $loader;
        $this->executor = $executor;
        $this->kernel = $kernel;
        $this->fixtures = $fixtures ?: new \ArrayObject([]);
    }

    protected function configure()
    {
        $this
            ->setDescription('Loads data fixtures from your bundles DataFixtures/Document directory.')
            ->addOption('fixtures', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The directory or file to load data fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures to the existing data - will not purge the workspace.')
            ->addOption('no-initialize', null, InputOption::VALUE_NONE, 'Do not run the repository initializers after purging the repository.')
            ->setHelp(<<<'EOT'
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $noInitialize = $input->getOption('no-initialize');
        $append = $input->getOption('append');

        if ($input->isInteractive() && !$append) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion(
                '<question>Careful, database will be purged. Do you want to continue y/n ?</question>',
                false
            );

            $confirmed = $helper->ask($input, $output, $question);

            if (!$confirmed) {
                return 0;
            }
        }

        $fixturesOption = $input->getOption('fixtures');

        $fixtures = [];
        if (!$fixturesOption) {
            $fixtures = iterator_to_array($this->fixtures);
        } else {
            // if specific fixture given use class name as identicator
            foreach ($this->fixtures as $fixture) {
                if (in_array(get_class($fixture), $fixturesOption)) {
                    $fixtures[] = $fixture;
                }
            }
        }

        $legacyPaths = [];

        // if specific fixture given but no classes found the given fixtures should be handled as legacy paths
        if ($fixturesOption && empty($fixtures)) {
            $legacyPaths = $fixturesOption;
        }

        $candidatePaths = [];

        // load all legacy paths if no fixtures option is given
        if (!$fixturesOption) {
            $directories = array_map(
                function(BundleInterface $bundle) {
                    return $bundle->getPath();
                },
                $this->kernel->getBundles()
            );

            if (method_exists($this->kernel, 'getRootDir')) {
                $directories[] = $this->kernel->getRootDir();
            }

            foreach ($directories as $directory) {
                $candidatePath = $directory . '/DataFixtures/Document';
                $candidatePaths[] = $candidatePath;
                if (file_exists($candidatePath)) {
                    $legacyPaths[] = $candidatePath;
                }
            }
        }

        if (empty($legacyPaths) && empty($fixtures)) {
            $output->writeln(
                '<info>Could not find any candidate fixture paths.</info>'
            );

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('Looked for: </comment>%s<comment>"</comment>',
                    implode('"<comment>", "</comment>', $candidatePaths)
                ));

                $output->writeln(sprintf('Looked for classes: </comment>%s<comment>"</comment>',
                    implode('"<comment>", "</comment>', array_map(function($fixture) {
                        return get_class($fixture);
                    }, iterator_to_array($this->fixtures)))
                ));
            }

            return 0;
        }

        if (!empty($legacyPaths)) {
            $legacyFixtures = $this->loader->load($legacyPaths);

            foreach ($legacyFixtures as $key => $fixture) {
                foreach ($fixtures as $existFixture) {
                    // remove legacy fixtures which are correctly injected
                    if (get_class($fixture) === get_class($existFixture)) {
                        unset($legacyFixtures[$key]);

                        continue 2;
                    }
                }

                @trigger_error(
                    sprintf(
                        'Loading fixtures out of folders is deprecated since sulu/sulu 2.1,' . PHP_EOL .
                        'tag "%s" as a service with "sulu.document_manager_fixture" instead.',
                        get_class($fixture)
                    ),
                    E_USER_DEPRECATED
                );
            }

            // merge legacy and exist fixtures together
            $fixtures = array_merge(
                $fixtures,
                $legacyFixtures
            );
        }

        $this->executor->execute($fixtures, false === $append, false === $noInitialize, $output);

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Done. Executed </info>%s</info><info> fixtures.</info>',
            count($fixtures)
        ));

        return 0;
    }
}
