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

use Jackalope\Property;
use Jackalope\Query\QueryManager;
use Jackalope\Query\Row;
use Jackalope\Session;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use PHPCR\Util\QOM\QueryBuilder;
use PHPCR\NodeInterface;

/**
 * Copy internationalized properties from one locale to another
 */
class ContentLocaleCopyCommand extends ContainerAwareCommand
{
    /**
     * Additional information will be written if true
     * @var boolean
     */
    private $verbose;

    /**
     * The namespace for languages
     * @var string
     */
    private $languageNamespace;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var QueryManager
     */
    private $queryManager;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * {@inheritDoc}
     */
    public function configure()
    {
        $this->setName('sulu:content:locale-copy');
        $this->setDescription('Copy nodes from one locale to another');
        $this->setHelp(<<<EOT
The <info>%command.name%</info> command copies the internationalized properties matching <info>srcLocale</info>
to <info>destLocale</info> on all nodes which descend from the given path.

    %command.full_name% /cms/sulu_io/contents de en --dry-run

You can overwrite existing values using the <info>overwrite</info> option:

    %command.full_name% /cms/sulu_io/contents de en --overwrite --dry-run

Remove the <info>dry-run</info> option to actually persist the changes.
EOT
    );
        $this->addArgument('path', InputArgument::REQUIRED, 'Copy locales in nodes which descend from this path (e.g. /)');
        $this->addArgument('srcLocale', InputArgument::REQUIRED, 'Locale to copy from (e.g. de)');
        $this->addArgument('destLocale', InputArgument::REQUIRED, 'Locale to copy to (e.g. en)');
        $this->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite existing locales');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist changes');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $srcLocale = $input->getArgument('srcLocale');
        $destLocale = $input->getArgument('destLocale');
        $overwrite = $input->getOption('overwrite');
        $dryRun = $input->getOption('dry-run');

        $this->verbose = $input->getOption('verbose');
        $this->session = $this->getContainer()->get('doctrine_phpcr')->getManager()->getPhpcrSession();
        $this->queryManager = $this->session->getWorkspace()->getQueryManager();
        $this->languageNamespace = $this->getContainer()->getParameter('sulu.content.language.namespace');

        $this->output = $output;

        $this->copyNodes($path, $srcLocale, $destLocale, $overwrite);

        if (false === $dryRun) {
            $this->output->writeln('<info>Saving ...</info>');
            $this->session->save();
            $this->output->writeln('<info>Done</info>');
        } else {
            $this->output->writeln('<info>Dry run complete</info>');
        }
    }

    private function copyNodes($path, $srcLocale, $destLocale, $overwrite)
    {
        $nodes = $this->getRowIterator($path);

        foreach ($nodes as $row) {
            /** @var Row $row */
            $this->copyLocale($row->getNode(), $srcLocale, $destLocale, $overwrite);
        }
    }

    private function getRowIterator($path)
    {
        $qb = new QueryBuilder($this->queryManager->getQOMFactory());
        $qomf = $qb->qomf();

        $qb->from($qomf->selector('a', 'nt:unstructured'))->where(
            $qomf->descendantNode('a', $path)
        );
        $query = $qb->getQuery();

        return $query->execute();
    }

    private function copyLocale(NodeInterface $node, $srcLocale, $destLocale, $overwrite)
    {
        $srcPrefix = $this->languageNamespace . ':' . $srcLocale . '-';
        $destPrefix = $this->languageNamespace . ':' . $destLocale . '-';
        /** @var Property[] $properties */
        $properties = array();

        foreach ($node->getProperties() as $name => $property) {
            if (0 === strpos($name, $srcPrefix)) {
                $properties[$name] = $property;
            }
        }

        if (!$properties) {
            return;
        }

        $this->output->writeln('<info>Processing: </info>' . $node->getPath());

        foreach ($properties as $name => $property) {
            $suluName = substr($name, strlen($srcPrefix));
            $destName = $destPrefix . $suluName;

            if ($node->hasProperty($destName) && false === $overwrite) {
                $this->output->writeln(sprintf('    Property exists: %s <comment>(use overwrite option to force)</comment>', $destName));
                continue;
            }

            $node->setProperty($destName, $property->getValue(), $property->getType());

            if ($this->verbose) {
                $this->output->writeln(sprintf(
                    '    <info>%s</info> > <comment>%s</comment>',
                    $name,
                    $destName
                ));
            }
        }
    }
}
