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

namespace Sulu\Content\Infrastructure\Symfony\Command;

use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ResourceLoader\Loader\CachedResourceLoader;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('sulu:debug:content', description: 'Debugging the content services to see if they are correctly registered')]
final class DebugContentCommand extends Command
{
    /**
     * @param iterable<string, ResourceLoaderInterface> $loaders
     * @param iterable<string, ResolverInterface> $resolvers
     */
    public function __construct(
        private readonly iterable $loaders,
        private readonly iterable $resolvers,
        private readonly iterable $propertyResolver,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Loaders');
        $this->printLoaders($io, $this->loaders);

        $io->title('Content resolvers');
        $this->printContentResolvers($io, $this->resolvers);

        $io->title('Property resolvers');
        $this->printContentResolvers($io, $this->propertyResolver);

        return Command::SUCCESS;
    }

    /**
     * @param iterable<string, ResourceLoaderInterface> $loaders
     */
    private function printLoaders(SymfonyStyle $io, iterable $loaders): void
    {
        $tableData = [];
        foreach ($this->loaders as $key => $loader) {
            $cached = false;
            if ($loader instanceof CachedResourceLoader) {
                $loader = $loader->getInnerClass();
                $cached = true;
            }

            $tableData[] = [
                $key,
                $loader,
                $cached ? 'x' : '',
            ];
        }

        $io->table(['Content Type', 'Loader Class', 'Cached'], $tableData);
    }

    /**
     * @param iterable<string, ResolverInterface> $resolvers
     */
    private function printContentResolvers(SymfonyStyle $io, iterable $resolvers): void
    {
        $tableData = [];
        foreach ($resolvers as $type => $resolver) {
            $tableData[] = [$type, $resolver::class];
        }
        $io->table(['Type', 'Resolver Class'], $tableData);
    }

    private function printPropertyResolver(SymfonyStyle $io, iterable $resolvers): void
    {
        $tableData = [];
        foreach ($resolvers as $type => $resolver) {
            $tableData[] = [$type, $resolver::class];
        }
        $io->table(['Type', 'Property resolver class'], $tableData);
    }
}
