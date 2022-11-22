<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MigrationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @final
 */
class MigrationDiffCommand extends Command
{
    protected static $defaultName = 'sulu:migration:migration-diff';

    private static $databases = [
        'MySQL 8' => 'mysql://root:ChangeMe@127.0.0.1:3306/su_myapp?serverVersion=8.0&charset=utf8mb4',
        'PostgreSQL 13' => 'postgresql://symfony:ChangeMe@127.0.0.1:5432/su_myapp?serverVersion=13&charset=utf8',
        'MariaDB 10' => 'mysql://root:@127.0.0.1:3307/su_myapp?serverVersion=mariadb-10.6.4&charset=utf8mb4',
    ];

    protected function configure()
    {
        $this->setDescription(
            'A command used for core developers to generate doctrine migrations.'
        );
        $this->addOption('console-path', null, InputOption::VALUE_REQUIRED, 'Path to the bin/console script', 'bin/console');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consolePath = $input->getOption('console-path');
        $ui = new SymfonyStyle($input, $output);

        foreach (static::$databases as $database => $databaseUrl) {
            $ui->section('Create Database: ' . $database);

            $envs = ['DATABASE_URL' => $databaseUrl]; // TODO not working like expected

            $createDatabaseCommand = new Process([
                \PHP_BINARY,
                $consolePath,
                'doctrine:database:create',
                '--if-not-exists',
            ], null, $envs);

            $createDatabaseCommand->run(function($type, $buffer) use ($ui) {
                $ui->write($buffer);
            });

            if ($exitCode = $createDatabaseCommand->getExitCode()) {
                return $exitCode;
            }

            $ui->section('Run Migrations: ' . $database);

            $createDatabaseCommand = new Process([
                \PHP_BINARY,
                $consolePath,
                'doctrine:migrations:migrate',
                '--allow-no-migration',
                '--no-interaction',
            ], null, $envs);

            $createDatabaseCommand->run(function($type, $buffer) use ($ui) {
                $ui->write($buffer);
            });

            if ($exitCode = $createDatabaseCommand->getExitCode()) {
                return $exitCode;
            }

            $ui->section('Create new Migration: ' . $database);

            $createDatabaseCommand = new Process([
                \PHP_BINARY,
                $consolePath,
                'doctrine:migrations:diff',
                '--no-interaction',
                '--check-database-platform',
                'true',
                '--allow-empty-diff',
            ], null, $envs);

            $createDatabaseCommand->run(function($type, $buffer) use ($ui) {
                $ui->write($buffer);
            });

            if ($exitCode = $createDatabaseCommand->getExitCode()) {
                return $exitCode;
            }
        }

        return 0;
    }
}
