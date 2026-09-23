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
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Content\Application\ResourceLoader\Loader\CachedResourceLoader;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal the command can be used and console command call is under bc promise but no bc promise is given for this PHP class itself
 * as it may will have additional dependencies in future
 */
#[AsCommand('sulu:content:debug', description: 'Debugging the content services to see if they are correctly registered')]
final class DebugContentCommand extends Command
{
    /**
     * @param iterable<string, ResourceLoaderInterface> $loader
     * @param iterable<string, ResolverInterface> $resolver
     * @param iterable<string, PropertyResolverInterface> $propertyResolver
     */
    public function __construct(
        private readonly iterable $loader,
        private readonly iterable $resolver,
        private readonly iterable $propertyResolver,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->printLoaders($io, $this->loader);

        $this->printContentResolvers($io, $this->resolver);

        $this->printPropertyResolver($io, $this->propertyResolver);

        return Command::SUCCESS;
    }

    /**
     * @param iterable<string, ResourceLoaderInterface> $loaders
     */
    private function printLoaders(SymfonyStyle $io, iterable $loaders): void
    {
        $io->title('Resource loaders');

        $tableData = [];
        foreach ($loaders as $key => $loader) {
            if ($loader instanceof CachedResourceLoader) {
                $loader = $loader->getInnerClass();
            }

            $tableData[] = [
                $key,
                $loader,
            ];
        }

        $io->table(['Type', 'Loader Class'], $tableData);
    }

    /**
     * @param iterable<string, ResolverInterface> $resolvers
     */
    private function printContentResolvers(SymfonyStyle $io, iterable $resolvers): void
    {
        $io->title('Dimension Content resolvers');

        $tableData = [];
        foreach ($resolvers as $type => $resolver) {
            $tableData[] = [$type, $resolver::class];
        }
        $io->table(['Type', 'Resolver Class'], $tableData);
    }

    /**
     * @param iterable<string, PropertyResolverInterface> $resolvers
     */
    private function printPropertyResolver(SymfonyStyle $io, iterable $resolvers): void
    {
        $io->title('Property resolvers');

        $io->info('If a type does not have an explicit property resolver (eg. "text" or "text_area") the "default" property resolver is used.');

        $tableData = [];
        foreach ($resolvers as $type => $resolver) {
            $tableData[] = ['default' === $type ? '->' : '', $type, $resolver::class];
        }
        $io->table(['#', 'Type', 'Property resolver class'], $tableData);
    }
}
