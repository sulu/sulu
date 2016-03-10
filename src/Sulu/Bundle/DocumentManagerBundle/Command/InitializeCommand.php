<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Initialize the content repository as managed by the document manager.
 */
class InitializeCommand extends Command
{
    /**
     * @var Initializer
     */
    private $initializer;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    public function __construct(
        Initializer $initializer,
        QuestionHelper $questionHelper = null
    ) {
        parent::__construct();
        $this->initializer = $initializer;
        $this->questionHelper = $questionHelper ?: new QuestionHelper();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sulu:document:initialize')
            ->setDescription('Initialize the content repository/repositories.')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge the content repository before initialization.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Do not ask for confiration.')
            ->setHelp(<<<'EOT'
Initialize the PHPCR content repository. This command
will all registered initializers. 

    <info>$ %command.full_name%</info>
    
WARNING: Initializers SHOULD be idempotent and it SHOULD be safe to run this
         command multiple times - but as we have no control over which initializers are
         registered and what they do this cannot be guaranteed, so use at your own
         discretion on a system that has sensitive data.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $purge = $input->getOption('purge');
        $force = $input->getOption('force');

        if ($purge && false === $force) {
            $question = new ConfirmationQuestion('<question>Are you sure you want to purge ALL the configured workspaces?</>', false);
            if (false === $this->questionHelper->ask($input, $output, $question)) {
                $output->writeln('Cancelled');

                return;
            }
        }

        $this->initializer->initialize($output, $purge);
    }
}
