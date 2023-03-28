<?php


declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\PHPCR\PropertyParser\PropertyParserInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PhpCRCleanerCommand extends Command
{
    protected static $defaultName = 'sulu:document:clean';

    public function __construct(
        private PropertyParserInterface $phpcrPropertyParser,
        private SessionInterface $session,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $rows = $queryManager->createQuery('SELECT * FROM [nt:unstructured]', 'JCR-SQL2')->execute();

        $progressBar = new ProgressBar($output);
        foreach ($progressBar->iterate($rows->getNodes()) as $node) {
            $progressBar->setMessage($node->getPath());

            $propertyData = $this->phpcrPropertyParser->parse($node->getPropertiesValues('i18n:*'));
            $this->recursiveRemove($propertyData, $node);
            $this->session->save();
        }
        $progressBar->finish();
        $this->session->save();

        return 0;
    }

    /**
     * Iterates through the tree-like structure that the properties are now and removes excessive nodes.
     */
    private function recursiveRemove(array $propertyData, NodeInterface $node): void
    {
        foreach ($propertyData as $key => $value) {
            if (\is_array($value)) {
                $this->recursiveRemove($value, $node);
            }
        }

        if (!\array_key_exists('length', $propertyData)) {
            return;
        }

        $length = $propertyData['length']->value;
        foreach ($propertyData as $key => $children) {
            if ('length' === $key) {
                continue;
            }

            if ($key >= $length) {
                $this->deleteProperties($children, $node);
            }
        }
    }

    /**
     * Iterates over all keys under a given node and removes them from phpcr.
     */
    private function deleteProperties(array $data, NodeInterface $node): void
    {
        foreach ($this->phpcrPropertyParser->keyIterator($data) as $key) {
            try {
                $node->getProperty($key)->remove();
            } catch (PathNotFoundException) {
            }
        }
    }
}
